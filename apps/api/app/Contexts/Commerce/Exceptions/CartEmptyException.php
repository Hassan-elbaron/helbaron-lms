<?php

namespace App\Contexts\Commerce\Exceptions;

class CartEmptyException extends CommerceException
{
    protected string $errorCode = 'COMMERCE_CART_EMPTY';

    protected int $status = 422;

    public function __construct(string $message = 'Your cart is empty.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
