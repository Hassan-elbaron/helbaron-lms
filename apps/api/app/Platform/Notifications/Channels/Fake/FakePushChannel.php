<?php

namespace App\Platform\Notifications\Channels\Fake;

use App\Platform\Notifications\Contracts\NotificationChannel;
use App\Platform\Notifications\Contracts\Providers\PushProvider;
use App\Platform\Notifications\Data\RenderedMessage;
use App\Platform\Notifications\Models\NotificationDelivery;

class FakePushChannel implements NotificationChannel
{
    public function __construct(private readonly PushProvider $push) {}

    public function send(NotificationDelivery $delivery, RenderedMessage $message): void
    {
        $to = 'user:'.$delivery->notification->user_id;
        $this->push->send($to, $message->subject, $message->body);
    }
}
