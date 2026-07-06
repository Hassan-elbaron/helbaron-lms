<?php

namespace App\Domains\Live\Exceptions;

class AlreadyRegisteredException extends LiveException
{
    protected string $errorCode = 'LIVE_ALREADY_REGISTERED';

    protected int $status = 409;

    public function __construct(string $message = 'You are already registered for this session.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
