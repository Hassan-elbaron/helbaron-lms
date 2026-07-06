<?php

namespace App\Domains\Commerce\Listeners;

use App\Domains\Commerce\Actions\Payment\FulfillOrderAction;
use App\Domains\Commerce\Events\OrderPaid;

/**
 * Attempts fulfillment when an order is paid. FulfillOrderAction only grants if the contract
 * is also accepted; otherwise it no-ops until ContractAccepted fires.
 */
class FulfillOnOrderPaid
{
    public function __construct(private readonly FulfillOrderAction $fulfill) {}

    public function handle(OrderPaid $event): void
    {
        $this->fulfill->execute($event->order);
    }
}
