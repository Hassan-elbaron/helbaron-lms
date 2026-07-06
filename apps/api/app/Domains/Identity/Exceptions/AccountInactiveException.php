<?php

namespace App\Domains\Identity\Exceptions;

class AccountInactiveException extends IdentityException
{
    protected string $errorCode = 'AUTH_ACCOUNT_INACTIVE';

    protected int $status = 403;

    public function __construct(string $message = 'Account is inactive.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
