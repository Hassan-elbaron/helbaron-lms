<?php

namespace App\Contexts\Commerce\Actions\Payment;

use App\Contexts\Commerce\Contracts\PaymentGateway;
use App\Contexts\Commerce\Enums\OrderStatus;
use App\Contexts\Commerce\Enums\TransactionStatus;
use App\Contexts\Commerce\Enums\TransactionType;
use App\Contexts\Commerce\Events\OrderRefunded;
use App\Contexts\Commerce\Exceptions\OrderNotRefundableException;
use App\Contexts\Commerce\Models\Order;
use App\Contexts\Commerce\Models\PaymentTransaction;
use App\Contexts\Commerce\Payments\Data\RefundRequest;
use App\Platform\Shared\Actions\BaseAction;
use App\Platform\Shared\Audit\AuditLogger;
use Throwable;

/**
 * Refunds a paid order via the gateway, records the refund transaction, and marks the order
 * refunded. Enrollment revocation happens via the OrderRefunded listener (Learning unenroll).
 *
 * Concurrency-safe and idempotent: the order row is locked and transitioned Paid -> Refunding
 * BEFORE the gateway call, and the gateway call runs OUTSIDE any DB transaction (network I/O
 * never holds row locks). A concurrent or repeated refund attempt sees a non-Paid status and
 * fails with a domain error instead of double-refunding.
 */
class RefundOrderAction extends BaseAction
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(Order $order): Order
    {
        // Phase 1: lock the order, re-check refundability under the lock, and claim it.
        $charge = $this->transaction(function () use (&$order): ?PaymentTransaction {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status !== OrderStatus::Paid) {
                throw new OrderNotRefundableException;
            }

            $alreadyRefunded = $order->transactions()
                ->where('type', TransactionType::Refund->value)
                ->where('status', TransactionStatus::Succeeded->value)
                ->exists();

            if ($alreadyRefunded) {
                throw new OrderNotRefundableException('This order has already been refunded.');
            }

            $order->forceFill(['status' => OrderStatus::Refunding->value])->save();

            return $order->transactions()
                ->where('type', TransactionType::Charge->value)
                ->where('status', TransactionStatus::Succeeded->value)
                ->latest('id')
                ->first();
        });

        // Phase 2: call the provider OUTSIDE any DB transaction.
        try {
            $result = $this->gateway->refund(new RefundRequest(
                providerReference: (string) ($charge?->provider_reference ?? ''),
                amountMinor: $order->total_minor,
                currency: $order->currency,
            ));
        } catch (Throwable $e) {
            $this->release($order);

            throw $e;
        }

        // Phase 3: record the outcome and finalize the status.
        $refunded = $this->transaction(function () use ($order, $result): Order {
            PaymentTransaction::create([
                'order_id' => $order->id,
                'provider' => (string) config('commerce.payment.provider'),
                'provider_reference' => $result->providerReference,
                'type' => TransactionType::Refund->value,
                'status' => $result->isSucceeded() ? TransactionStatus::Succeeded->value : TransactionStatus::Failed->value,
                'amount_minor' => $order->total_minor,
                'currency' => $order->currency,
            ]);

            $order->forceFill($result->isSucceeded()
                ? ['status' => OrderStatus::Refunded->value, 'refunded_at' => now()]
                : ['status' => OrderStatus::Paid->value])->save();

            return $order;
        });

        if ($refunded->status !== OrderStatus::Refunded) {
            throw new OrderNotRefundableException('The payment provider declined the refund.');
        }

        $this->audit->log('order.refunded', $refunded, [
            'amount_minor' => $refunded->total_minor,
            'currency' => $refunded->currency,
        ]);

        OrderRefunded::dispatch($refunded);

        return $refunded;
    }

    /** Compensating action: return a claimed (Refunding) order to Paid so it can be retried. */
    private function release(Order $order): void
    {
        Order::whereKey($order->id)
            ->where('status', OrderStatus::Refunding->value)
            ->update(['status' => OrderStatus::Paid->value]);
    }
}
