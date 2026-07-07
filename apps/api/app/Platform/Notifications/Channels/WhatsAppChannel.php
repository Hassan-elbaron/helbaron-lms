<?php

namespace App\Platform\Notifications\Channels;

use App\Platform\Notifications\Contracts\NotificationChannel;
use App\Platform\Notifications\Data\RenderedMessage;
use App\Platform\Notifications\Models\NotificationDelivery;

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
