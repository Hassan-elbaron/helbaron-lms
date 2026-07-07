<?php

namespace App\Platform\Identity\Exceptions;

class AccountLockedException extends IdentityException
{
    protected string $errorCode = 'AUTH_ACCOUNT_LOCKED';

    protected int $status = 423;

    public function __construct(string $message = 'Account is temporarily locked.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
