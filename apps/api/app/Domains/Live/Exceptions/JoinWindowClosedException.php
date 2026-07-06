<?php

namespace App\Domains\Live\Exceptions;

class JoinWindowClosedException extends LiveException
{
    protected string $errorCode = 'LIVE_JOIN_WINDOW_CLOSED';

    protected int $status = 422;

    public function __construct(string $message = 'The join window is not open yet.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
