<?php

namespace App\Platform\Notifications\Channels\Fake;

use App\Platform\Notifications\Contracts\NotificationChannel;
use App\Platform\Notifications\Contracts\Providers\SmsProvider;
use App\Platform\Notifications\Data\RenderedMessage;
use App\Platform\Notifications\Models\NotificationDelivery;

class FakeSmsChannel implements NotificationChannel
{
    public function __construct(private readonly SmsProvider $sms) {}

    public function send(NotificationDelivery $delivery, RenderedMessage $message): void
    {
        $to = (string) ($delivery->notification->user?->phone ?? '');
        $this->sms->send($to, $message->body);
    }
}
