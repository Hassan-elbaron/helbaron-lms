<?php

namespace App\Domains\Learning\Exceptions;

class LessonLockedException extends LearningException
{
    protected string $errorCode = 'LEARNING_LESSON_LOCKED';

    protected int $status = 403;

    public function __construct(string $message = 'This lesson is locked until its prerequisites are completed.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
