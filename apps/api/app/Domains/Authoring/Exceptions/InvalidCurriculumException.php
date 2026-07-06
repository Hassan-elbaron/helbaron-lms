<?php

namespace App\Domains\Authoring\Exceptions;

class InvalidCurriculumException extends AuthoringException
{
    protected string $errorCode = 'AUTHORING_INVALID_CURRICULUM';

    protected int $status = 422;

    public function __construct(string $message = 'The curriculum is invalid.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
