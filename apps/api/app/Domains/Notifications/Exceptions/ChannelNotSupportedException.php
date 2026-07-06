<?php

namespace App\Domains\Notifications\Exceptions;

class ChannelNotSupportedException extends NotificationException
{
    protected string $errorCode = 'NOTIF_CHANNEL_UNSUPPORTED';

    protected int $status = 422;

    public function __construct(string $message = 'This delivery channel is not supported.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
