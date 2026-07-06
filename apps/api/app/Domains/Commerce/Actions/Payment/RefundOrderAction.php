<?php

namespace App\Domains\Commerce\Actions\Payment;

use App\Domains\Commerce\Contracts\PaymentGateway;
use App\Domains\Commerce\Enums\OrderStatus;
use App\Domains\Commerce\Enums\TransactionStatus;
use App\Domains\Commerce\Enums\TransactionType;
use App\Domains\Commerce\Events\OrderRefunded;
use App\Domains\Commerce\Exceptions\OrderNotRefundableException;
use App\Domains\Commerce\Models\Order;
use App\Domains\Commerce\Models\PaymentTransaction;
use App\Domains\Commerce\Payments\Data\RefundRequest;
use App\Shared\Actions\BaseAction;

/**
 * Refunds a paid order via the gateway, records the refund transaction, and marks the order
 * refunded. Enrollment revocation happens via the OrderRefunded listener (Learning unenroll).
 */
class RefundOrderAction extends BaseAction
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function execute(Order $order): Order
    {
        if ($order->status !== OrderStatus::Paid) {
            throw new OrderNotRefundableException;
        }

        $charge = $order->transactions()->where('type', 'charge')->where('status', 'succeeded')->latest('id')->first();

        $refunded = $this->transaction(function () use ($order, $charge): Order {
            $result = $this->gateway->refund(new RefundRequest(
                providerReference: (string) ($charge?->provider_reference ?? ''),
                amountMinor: $order->total_minor,
                currency: $order->currency,
            ));

            PaymentTransaction::create([
                'order_id' => $order->id,
                'provider' => (string) config('commerce.payment.provider'),
                'provider_reference' => $result->providerReference,
                'type' => TransactionType::Refund->value,
                'status' => $result->isSucceeded() ? TransactionStatus::Succeeded->value : TransactionStatus::Failed->value,
                'amount_minor' => $order->total_minor,
                'currency' => $order->currency,
            ]);

            $order->forceFill(['status' => OrderStatus::Refunded->value, 'refunded_at' => now()])->save();

            return $order;
        });

        OrderRefunded::dispatch($refunded);

        return $refunded;
    }
}
