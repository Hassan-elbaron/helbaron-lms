<?php

namespace App\Domains\Notifications\Channels\Fake;

use App\Domains\Notifications\Contracts\NotificationChannel;
use App\Domains\Notifications\Contracts\Providers\SmsProvider;
use App\Domains\Notifications\Data\RenderedMessage;
use App\Domains\Notifications\Models\NotificationDelivery;

class FakeSmsChannel implements NotificationChannel
{
    public function __construct(private readonly SmsProvider $sms) {}

    public function send(NotificationDelivery $delivery, RenderedMessage $message): void
    {
        $to = (string) ($delivery->notification->user?->phone ?? '');
        $this->sms->send($to, $message->body);
    }
}
