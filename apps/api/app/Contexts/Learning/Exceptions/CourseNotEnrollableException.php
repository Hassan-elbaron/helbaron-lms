<?php

namespace App\Contexts\Learning\Exceptions;

class CourseNotEnrollableException extends LearningException
{
    protected string $errorCode = 'LEARNING_COURSE_NOT_ENROLLABLE';

    protected int $status = 422;

    public function __construct(string $message = 'This course cannot be enrolled in.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
