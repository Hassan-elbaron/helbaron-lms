<?php

namespace App\Contexts\Commerce\Exceptions;

class CouponExhaustedException extends CommerceException
{
    protected string $errorCode = 'COMMERCE_COUPON_EXHAUSTED';

    protected int $status = 422;

    public function __construct(string $message = 'The coupon redemption limit has been reached.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
