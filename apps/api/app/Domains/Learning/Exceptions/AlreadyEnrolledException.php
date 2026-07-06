<?php

namespace App\Domains\Learning\Exceptions;

class AlreadyEnrolledException extends LearningException
{
    protected string $errorCode = 'LEARNING_ALREADY_ENROLLED';

    protected int $status = 409;

    public function __construct(string $message = 'You are already enrolled in this course.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
