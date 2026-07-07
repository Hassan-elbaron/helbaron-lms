<?php

namespace App\Contexts\Commerce\Actions\Payment;

use App\Contexts\Commerce\Contracts\PaymentGateway;
use App\Contexts\Commerce\Enums\OrderStatus;
use App\Contexts\Commerce\Enums\TransactionStatus;
use App\Contexts\Commerce\Enums\TransactionType;
use App\Contexts\Commerce\Exceptions\OrderNotPayableException;
use App\Contexts\Commerce\Models\Order;
use App\Contexts\Commerce\Models\PaymentTransaction;
use App\Contexts\Commerce\Payments\Data\ChargeRequest;
use App\Contexts\Commerce\Payments\Data\ChargeResult;
use App\Platform\Shared\Actions\BaseAction;

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
