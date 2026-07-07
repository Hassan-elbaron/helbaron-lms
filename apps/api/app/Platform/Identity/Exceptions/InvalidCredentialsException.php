<?php

namespace App\Platform\Identity\Exceptions;

class InvalidCredentialsException extends IdentityException
{
    protected string $errorCode = 'AUTH_INVALID_CREDENTIALS';

    protected int $status = 401;

    public function __construct(string $message = 'Invalid credentials.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
