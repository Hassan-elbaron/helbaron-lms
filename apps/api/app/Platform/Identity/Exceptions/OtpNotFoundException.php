<?php

namespace App\Platform\Identity\Exceptions;

class OtpNotFoundException extends IdentityException
{
    protected string $errorCode = 'AUTH_OTP_NOT_FOUND';

    protected int $status = 404;

    public function __construct(string $message = 'No active code was found.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
