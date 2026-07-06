<?php

namespace App\Domains\Identity\Exceptions;

class MfaAlreadyEnabledException extends IdentityException
{
    protected string $errorCode = 'AUTH_MFA_ALREADY_ENABLED';

    protected int $status = 409;

    public function __construct(string $message = 'Multi-factor authentication is already enabled.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
