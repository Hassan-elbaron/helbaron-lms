<?php

namespace App\Domains\Authoring\Exceptions;

class PrerequisiteCycleException extends AuthoringException
{
    protected string $errorCode = 'AUTHORING_PREREQUISITE_CYCLE';

    protected int $status = 422;

    public function __construct(string $message = 'Prerequisites would create a cycle.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
