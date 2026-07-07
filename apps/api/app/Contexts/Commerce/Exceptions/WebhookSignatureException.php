<?php

namespace App\Contexts\Commerce\Exceptions;

class WebhookSignatureException extends CommerceException
{
    protected string $errorCode = 'COMMERCE_WEBHOOK_SIGNATURE';

    protected int $status = 400;

    public function __construct(string $message = 'Invalid webhook signature.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
