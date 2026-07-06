<?php

namespace App\Domains\Notifications\Channels;

use App\Domains\Notifications\Contracts\NotificationChannel;
use App\Domains\Notifications\Data\RenderedMessage;
use App\Domains\Notifications\Models\NotificationDelivery;

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
