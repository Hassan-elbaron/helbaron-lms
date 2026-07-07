<?php

namespace App\Platform\Identity\Exceptions;

class InvalidMfaCodeException extends IdentityException
{
    protected string $errorCode = 'AUTH_MFA_INVALID';

    protected int $status = 422;

    public function __construct(string $message = 'The multi-factor code is invalid.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
