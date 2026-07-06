<?php

namespace App\Domains\Live\Exceptions;

class InvalidSessionStateException extends LiveException
{
    protected string $errorCode = 'LIVE_INVALID_STATE';

    protected int $status = 422;

    public function __construct(string $message = 'The session is not in a valid state for this action.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
