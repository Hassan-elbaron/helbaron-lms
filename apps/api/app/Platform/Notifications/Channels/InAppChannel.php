<?php

namespace App\Platform\Notifications\Channels;

use App\Platform\Notifications\Contracts\NotificationChannel;
use App\Platform\Notifications\Data\RenderedMessage;
use App\Platform\Notifications\Models\NotificationDelivery;

/**
 * In-app delivery: the notification already lives in the center, so this just confirms delivery.
 */
class InAppChannel implements NotificationChannel
{
    public function send(NotificationDelivery $delivery, RenderedMessage $message): void
    {
        // Nothing external — the Notification row IS the in-app delivery.
    }
}
