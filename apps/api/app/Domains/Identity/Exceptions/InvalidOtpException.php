<?php

namespace App\Domains\Identity\Exceptions;

class InvalidOtpException extends IdentityException
{
    protected string $errorCode = 'AUTH_OTP_INVALID';

    protected int $status = 422;

    public function __construct(string $message = 'The provided code is invalid.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
