<?php

namespace App\Contexts\Commerce\Exceptions;

class OrderNotRefundableException extends CommerceException
{
    protected string $errorCode = 'COMMERCE_ORDER_NOT_REFUNDABLE';

    protected int $status = 409;

    public function __construct(string $message = 'This order cannot be refunded.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
