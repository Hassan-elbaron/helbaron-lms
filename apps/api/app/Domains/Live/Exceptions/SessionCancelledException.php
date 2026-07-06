<?php

namespace App\Domains\Live\Exceptions;

class SessionCancelledException extends LiveException
{
    protected string $errorCode = 'LIVE_SESSION_CANCELLED';

    protected int $status = 410;

    public function __construct(string $message = 'This session has been cancelled.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
