<?php

namespace App\Domains\Live\Exceptions;

class SessionNotOpenException extends LiveException
{
    protected string $errorCode = 'LIVE_SESSION_NOT_OPEN';

    protected int $status = 422;

    public function __construct(string $message = 'This session is not open for registration.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
