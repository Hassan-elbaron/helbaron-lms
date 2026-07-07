<?php

namespace App\Platform\Identity\Exceptions;

class ExpiredOtpException extends IdentityException
{
    protected string $errorCode = 'AUTH_OTP_EXPIRED';

    protected int $status = 410;

    public function __construct(string $message = 'The code has expired.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
