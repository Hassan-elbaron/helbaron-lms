<?php

namespace App\Domains\Live\Exceptions;

class InvalidTimezoneException extends LiveException
{
    protected string $errorCode = 'LIVE_INVALID_TIMEZONE';

    protected int $status = 422;

    public function __construct(string $message = 'The provided timezone is invalid.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
