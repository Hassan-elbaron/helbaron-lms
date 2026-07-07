<?php

namespace App\Platform\Notifications\Events;

use App\Platform\Notifications\Models\NotificationDelivery;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationDelivered
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly NotificationDelivery $delivery) {}
}
