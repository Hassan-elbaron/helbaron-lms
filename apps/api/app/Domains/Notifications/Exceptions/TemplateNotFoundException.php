<?php

namespace App\Domains\Notifications\Exceptions;

class TemplateNotFoundException extends NotificationException
{
    protected string $errorCode = 'NOTIF_TEMPLATE_NOT_FOUND';

    protected int $status = 422;

    public function __construct(string $message = 'No template was found for this notification.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
