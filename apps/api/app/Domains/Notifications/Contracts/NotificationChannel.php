<?php

namespace App\Domains\Notifications\Contracts;

use App\Domains\Notifications\Data\RenderedMessage;
use App\Domains\Notifications\Models\NotificationDelivery;

/**
 * Delivers a rendered message on one channel. Concrete channels wrap a provider; only Fake
 * providers exist, so nothing is ever sent for real. Throwing signals a retriable failure.
 */
interface NotificationChannel
{
    public function send(NotificationDelivery $delivery, RenderedMessage $message): void;
}
