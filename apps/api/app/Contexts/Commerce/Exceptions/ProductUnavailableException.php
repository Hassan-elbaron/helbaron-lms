<?php

namespace App\Contexts\Commerce\Exceptions;

class ProductUnavailableException extends CommerceException
{
    protected string $errorCode = 'COMMERCE_PRODUCT_UNAVAILABLE';

    protected int $status = 422;

    public function __construct(string $message = 'This product is not available for purchase.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
