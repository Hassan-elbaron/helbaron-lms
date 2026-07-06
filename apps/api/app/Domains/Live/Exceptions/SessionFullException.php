<?php

namespace App\Domains\Live\Exceptions;

class SessionFullException extends LiveException
{
    protected string $errorCode = 'LIVE_SESSION_FULL';

    protected int $status = 409;

    public function __construct(string $message = 'This session is at capacity.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
