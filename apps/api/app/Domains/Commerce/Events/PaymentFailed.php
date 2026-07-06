<?php

namespace App\Domains\Commerce\Events;

use App\Domains\Commerce\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentFailed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Order $order) {}
}
