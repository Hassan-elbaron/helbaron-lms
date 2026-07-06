<?php

namespace App\Domains\Notifications\Channels;

use App\Domains\Notifications\Contracts\NotificationChannel;
use App\Domains\Notifications\Data\RenderedMessage;
use App\Domains\Notifications\Models\NotificationDelivery;

/**
 * WhatsApp channel — STUB ONLY. No WhatsApp integration. Marks the delivery handled without
 * contacting any provider.
 */
class WhatsAppChannel implements NotificationChannel
{
    public function send(NotificationDelivery $delivery, RenderedMessage $message): void
    {
        // Intentionally a no-op stub; a real WhatsApp provider is out of scope.
    }
}
