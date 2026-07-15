<?php

namespace App\Contexts\Commerce\Actions\Checkout;

use App\Contexts\Commerce\Contracts\PaymentGateway;
use App\Contexts\Commerce\Enums\InvoiceStatus;
use App\Contexts\Commerce\Enums\OrderStatus;
use App\Contexts\Commerce\Enums\TransactionStatus;
use App\Contexts\Commerce\Enums\TransactionType;
use App\Contexts\Commerce\Events\OrderPlaced;
use App\Contexts\Commerce\Exceptions\CartEmptyException;
use App\Contexts\Commerce\Exceptions\CouponExhaustedException;
use App\Contexts\Commerce\Models\Coupon;
use App\Contexts\Commerce\Models\CouponRedemption;
use App\Contexts\Commerce\Models\Invoice;
use App\Contexts\Commerce\Models\Order;
use App\Contexts\Commerce\Models\OrderItem;
use App\Contexts\Commerce\Models\PaymentTransaction;
use App\Contexts\Commerce\Payments\Data\ChargeRequest;
use App\Contexts\Commerce\Services\CartService;
use App\Contexts\Commerce\Services\ContractService;
use App\Contexts\Commerce\Services\InvoiceNumberService;
use App\Platform\Shared\Actions\BaseAction;
use Throwable;

/**
 * Converts the cart into an order + invoice + a pending contract, records coupon redemption
 * under a lock, then initiates payment via the gateway abstraction. Confirmation arrives by
 * webhook. Enrollment is NOT granted here — only after payment + contract acceptance.
 *
 * The order (pending) + coupon redemption COMMIT before any gateway I/O, so no network call
 * ever runs inside a DB transaction. If the gateway call fails, a compensating transaction
 * marks the order failed and releases the coupon redemption. The order public_id doubles as
 * the gateway idempotency key so provider-side retries cannot double-charge.
 *
 * @phpstan-type CheckoutResult array{order: Order, contract: ?\App\Contexts\Commerce\Models\Contract, charge: \App\Contexts\Commerce\Payments\Data\ChargeResult}
 */
class CheckoutAction extends BaseAction
{
    public function __construct(
        private readonly CartService $carts,
        private readonly ContractService $contracts,
        private readonly InvoiceNumberService $invoiceNumbers,
        private readonly PaymentGateway $gateway,
    ) {}

    /** @return array{order: Order, contract: mixed, charge: mixed} */
    public function executeByUserId(int $userId): array
    {
        $cart = $this->carts->currentByUserId($userId)->load(['items.product', 'coupon']);

        if ($cart->items->isEmpty()) {
            throw new CartEmptyException;
        }

        // Phase 1: create the order, invoice, coupon redemption, and contract; COMMIT first.
        [$order, $contract] = $this->transaction(function () use ($userId, $cart): array {
            // Lock the coupon row to serialize redemption counting.
            $coupon = $cart->coupon;
            if ($coupon !== null) {
                $coupon = $coupon->newQuery()->whereKey($coupon->id)->lockForUpdate()->first();

                // Re-validate under the lock: the counter may have moved since it was applied.
                if ($coupon === null || $coupon->isExhausted()) {
                    throw new CouponExhaustedException;
                }
            }

            $totals = $this->carts->totals($cart);

            $order = Order::create([
                'user_id' => $userId,
                'status' => OrderStatus::Pending->value,
                'currency' => $cart->currency,
                'subtotal_minor' => $totals['subtotal_minor'],
                'discount_minor' => $totals['discount_minor'],
                'total_minor' => $totals['total_minor'],
                'coupon_id' => $coupon?->id,
                'placed_at' => now(),
            ]);

            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'title' => $item->product->title,
                    'unit_amount_minor' => $item->unit_amount_minor,
                ]);
            }

            Invoice::create([
                'order_id' => $order->id,
                'number' => $this->invoiceNumbers->next(),
                'status' => InvoiceStatus::Issued->value,
                'currency' => $order->currency,
                'total_minor' => $order->total_minor,
                'issued_at' => now(),
            ]);

            if ($coupon !== null) {
                $coupon->increment('redeemed_count');
                $coupon->redemptions()->create(['user_id' => $userId, 'order_id' => $order->id]);
            }

            $contract = $this->contracts->createForOrderByUserId($userId, $order);

            return [$order, $contract];
        });

        // Phase 2: gateway charge OUTSIDE any DB transaction.
        try {
            $charge = $this->gateway->charge(new ChargeRequest(
                reference: $order->public_id,
                amountMinor: $order->total_minor,
                currency: $order->currency,
                description: 'HElbaron order '.$order->public_id,
                idempotencyKey: $order->public_id,
            ));
        } catch (Throwable $e) {
            $this->compensate($order);

            throw $e;
        }

        // Phase 3: record the pending transaction, then empty the captured cart.
        $this->transaction(function () use ($order, $cart, $charge): void {
            PaymentTransaction::create([
                'order_id' => $order->id,
                'provider' => (string) config('commerce.payment.provider'),
                'provider_reference' => $charge->providerReference,
                'type' => TransactionType::Charge->value,
                'status' => TransactionStatus::Pending->value,
                'amount_minor' => $order->total_minor,
                'currency' => $order->currency,
            ]);

            // Empty the cart now that it is captured on the order.
            $this->carts->clear($cart);
        });

        OrderPlaced::dispatch($order);

        return ['order' => $order, 'contract' => $contract, 'charge' => $charge];
    }

    /** Compensating action: mark the order failed and release the coupon redemption. */
    private function compensate(Order $order): void
    {
        $this->transaction(function () use ($order): void {
            $order->forceFill(['status' => OrderStatus::Failed->value])->save();

            if ($order->coupon_id === null) {
                return;
            }

            $coupon = Coupon::whereKey($order->coupon_id)->lockForUpdate()->first();

            if ($coupon !== null && $coupon->redeemed_count > 0) {
                $coupon->decrement('redeemed_count');
            }

            CouponRedemption::where('order_id', $order->id)->delete();
        });
    }
}
