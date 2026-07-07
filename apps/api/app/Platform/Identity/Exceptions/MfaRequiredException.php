<?php

namespace App\Platform\Identity\Exceptions;

class MfaRequiredException extends IdentityException
{
    protected string $errorCode = 'AUTH_MFA_REQUIRED';

    protected int $status = 403;

    public function __construct(string $message = 'Multi-factor authentication is required.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
