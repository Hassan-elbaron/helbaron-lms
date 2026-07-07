<?php

namespace App\Contexts\Commerce\Exceptions;

class CouponExpiredException extends CommerceException
{
    protected string $errorCode = 'COMMERCE_COUPON_EXPIRED';

    protected int $status = 422;

    public function __construct(string $message = 'The coupon has expired.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
