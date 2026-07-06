<?php

namespace App\Domains\Commerce\Exceptions;

class OrderNotPayableException extends CommerceException
{
    protected string $errorCode = 'COMMERCE_ORDER_NOT_PAYABLE';

    protected int $status = 409;

    public function __construct(string $message = 'This order cannot be paid.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
