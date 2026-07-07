<?php

namespace App\Contexts\Learning\Exceptions;

class NotEnrolledException extends LearningException
{
    protected string $errorCode = 'LEARNING_NOT_ENROLLED';

    protected int $status = 403;

    public function __construct(string $message = 'You are not enrolled in this course.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
