<?php

namespace App\Domains\Commerce\Exceptions;

class PaymentFailedException extends CommerceException
{
    protected string $errorCode = 'COMMERCE_PAYMENT_FAILED';

    protected int $status = 402;

    public function __construct(string $message = 'The payment could not be completed.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
