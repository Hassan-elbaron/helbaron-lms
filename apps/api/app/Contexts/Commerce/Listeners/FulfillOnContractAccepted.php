<?php

namespace App\Contexts\Commerce\Listeners;

use App\Contexts\Commerce\Actions\Payment\FulfillOrderAction;
use App\Contexts\Commerce\Events\ContractAccepted;

/**
 * Attempts fulfillment when a contract is accepted (the other half of the paid+accepted gate).
 */
class FulfillOnContractAccepted
{
    public function __construct(private readonly FulfillOrderAction $fulfill) {}

    public function handle(ContractAccepted $event): void
    {
        $order = $event->contract->order;

        if ($order !== null) {
            $this->fulfill->execute($order);
        }
    }
}
