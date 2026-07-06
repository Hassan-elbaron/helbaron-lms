<?php

namespace App\Domains\Live\Exceptions;

class NotRegisteredException extends LiveException
{
    protected string $errorCode = 'LIVE_NOT_REGISTERED';

    protected int $status = 403;

    public function __construct(string $message = 'You are not registered for this session.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
