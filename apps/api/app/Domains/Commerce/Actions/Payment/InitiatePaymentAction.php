<?php

namespace App\Domains\Commerce\Actions\Payment;

use App\Domains\Commerce\Contracts\PaymentGateway;
use App\Domains\Commerce\Enums\OrderStatus;
use App\Domains\Commerce\Enums\TransactionStatus;
use App\Domains\Commerce\Enums\TransactionType;
use App\Domains\Commerce\Exceptions\OrderNotPayableException;
use App\Domains\Commerce\Models\Order;
use App\Domains\Commerce\Models\PaymentTransaction;
use App\Domains\Commerce\Payments\Data\ChargeRequest;
use App\Domains\Commerce\Payments\Data\ChargeResult;
use App\Shared\Actions\BaseAction;

/**
 * (Re)initiates payment for a pending order via the gateway abstraction.
 */
class InitiatePaymentAction extends BaseAction
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function execute(Order $order): ChargeResult
    {
        if (! in_array($order->status, [OrderStatus::Pending, OrderStatus::Failed], true)) {
            throw new OrderNotPayableException;
        }

        return $this->transaction(function () use ($order): ChargeResult {
            $charge = $this->gateway->charge(new ChargeRequest(
                reference: $order->public_id,
                amountMinor: $order->total_minor,
                currency: $order->currency,
            ));

            $order->forceFill(['status' => OrderStatus::Pending->value])->save();

            PaymentTransaction::create([
                'order_id' => $order->id,
                'provider' => (string) config('commerce.payment.provider'),
                'provider_reference' => $charge->providerReference,
                'type' => TransactionType::Charge->value,
                'status' => TransactionStatus::Pending->value,
                'amount_minor' => $order->total_minor,
                'currency' => $order->currency,
            ]);

            return $charge;
        });
    }
}
