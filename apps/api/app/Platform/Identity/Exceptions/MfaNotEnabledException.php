<?php

namespace App\Platform\Identity\Exceptions;

class MfaNotEnabledException extends IdentityException
{
    protected string $errorCode = 'AUTH_MFA_NOT_ENABLED';

    protected int $status = 409;

    public function __construct(string $message = 'Multi-factor authentication is not enabled.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
