<?php

namespace App\Contexts\Commerce\Events;

use App\Contexts\Commerce\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderFulfilled
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Order $order) {}
}
