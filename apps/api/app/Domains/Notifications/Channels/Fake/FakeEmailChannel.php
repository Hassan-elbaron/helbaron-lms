<?php

namespace App\Domains\Notifications\Channels\Fake;

use App\Domains\Notifications\Contracts\NotificationChannel;
use App\Domains\Notifications\Contracts\Providers\MailProvider;
use App\Domains\Notifications\Data\RenderedMessage;
use App\Domains\Notifications\Models\NotificationDelivery;

class FakeEmailChannel implements NotificationChannel
{
    public function __construct(private readonly MailProvider $mail) {}

    public function send(NotificationDelivery $delivery, RenderedMessage $message): void
    {
        $to = (string) ($delivery->notification->user?->email ?? '');
        $this->mail->send($to, $message->subject, $message->body);
    }
}
