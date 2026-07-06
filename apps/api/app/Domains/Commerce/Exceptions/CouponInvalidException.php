<?php

namespace App\Domains\Commerce\Exceptions;

class CouponInvalidException extends CommerceException
{
    protected string $errorCode = 'COMMERCE_COUPON_INVALID';

    protected int $status = 422;

    public function __construct(string $message = 'The coupon is invalid.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
