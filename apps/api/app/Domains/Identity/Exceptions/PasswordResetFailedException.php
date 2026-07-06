<?php

namespace App\Domains\Identity\Exceptions;

class PasswordResetFailedException extends IdentityException
{
    protected string $errorCode = 'AUTH_PASSWORD_RESET_FAILED';

    protected int $status = 422;

    public function __construct(string $message = 'Password reset failed.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
