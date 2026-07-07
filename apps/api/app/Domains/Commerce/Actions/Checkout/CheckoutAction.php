<?php

namespace App\Domains\Commerce\Actions\Checkout;

use App\Domains\Commerce\Contracts\PaymentGateway;
use App\Domains\Commerce\Enums\InvoiceStatus;
use App\Domains\Commerce\Enums\OrderStatus;
use App\Domains\Commerce\Enums\TransactionStatus;
use App\Domains\Commerce\Enums\TransactionType;
use App\Domains\Commerce\Events\OrderPlaced;
use App\Domains\Commerce\Exceptions\CartEmptyException;
use App\Domains\Commerce\Models\Invoice;
use App\Domains\Commerce\Models\Order;
use App\Domains\Commerce\Models\OrderItem;
use App\Domains\Commerce\Models\PaymentTransaction;
use App\Domains\Commerce\Payments\Data\ChargeRequest;
use App\Domains\Commerce\Services\CartService;
use App\Domains\Commerce\Services\ContractService;
use App\Domains\Commerce\Services\InvoiceNumberService;
use App\Platform\Identity\Models\User;
use App\Platform\Shared\Actions\BaseAction;

/**
 * Converts the cart into an order + invoice + a pending contract, records coupon redemption
 * under a lock, then initiates payment via the gateway abstraction. Confirmation arrives by
 * webhook. Enrollment is NOT granted here — only after payment + contract acceptance.
 *
 * @phpstan-type CheckoutResult array{order: Order, contract: ?\App\Domains\Commerce\Models\Contract, charge: \App\Domains\Commerce\Payments\Data\ChargeResult}
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
    public function execute(User $user): array
    {
        $cart = $this->carts->current($user)->load(['items.product', 'coupon']);

        if ($cart->items->isEmpty()) {
            throw new CartEmptyException;
        }

        $result = $this->transaction(function () use ($user, $cart) {
            // Lock the coupon row to serialize redemption counting.
            $coupon = $cart->coupon;
            if ($coupon !== null) {
                $coupon = $coupon->newQuery()->whereKey($coupon->id)->lockForUpdate()->first();
            }

            $totals = $this->carts->totals($cart);

            $order = Order::create([
                'user_id' => $user->id,
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
                $coupon->redemptions()->create(['user_id' => $user->id, 'order_id' => $order->id]);
            }

            $contract = $this->contracts->createForOrder($user, $order);

            $charge = $this->gateway->charge(new ChargeRequest(
                reference: $order->public_id,
                amountMinor: $order->total_minor,
                currency: $order->currency,
                description: 'HElbaron order '.$order->public_id,
            ));

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

            return ['order' => $order, 'contract' => $contract, 'charge' => $charge];
        });

        OrderPlaced::dispatch($result['order']);

        return $result;
    }
}
