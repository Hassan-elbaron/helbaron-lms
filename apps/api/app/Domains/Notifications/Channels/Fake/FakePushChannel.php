<?php

namespace App\Domains\Notifications\Channels\Fake;

use App\Domains\Notifications\Contracts\NotificationChannel;
use App\Domains\Notifications\Contracts\Providers\PushProvider;
use App\Domains\Notifications\Data\RenderedMessage;
use App\Domains\Notifications\Models\NotificationDelivery;

class FakePushChannel implements NotificationChannel
{
    public function __construct(private readonly PushProvider $push) {}

    public function send(NotificationDelivery $delivery, RenderedMessage $message): void
    {
        $to = 'user:'.$delivery->notification->user_id;
        $this->push->send($to, $message->subject, $message->body);
    }
}
