<?php

namespace App\Platform\Notifications\Contracts;

use App\Platform\Notifications\Data\RenderedMessage;
use App\Platform\Notifications\Models\NotificationDelivery;

/**
 * Delivers a rendered message on one channel. Concrete channels wrap a provider; only Fake
 * providers exist, so nothing is ever sent for real. Throwing signals a retriable failure.
 */
interface NotificationChannel
{
    public function send(NotificationDelivery $delivery, RenderedMessage $message): void;
}
