<?php

namespace App\Platform\Notifications\Exceptions;

class DeliveryFailedException extends NotificationException
{
    protected string $errorCode = 'NOTIF_DELIVERY_FAILED';

    protected int $status = 500;

    public function __construct(string $message = 'Delivery failed.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
