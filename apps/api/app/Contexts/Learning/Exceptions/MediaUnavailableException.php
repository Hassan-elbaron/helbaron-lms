<?php

namespace App\Contexts\Learning\Exceptions;

class MediaUnavailableException extends LearningException
{
    protected string $errorCode = 'LEARNING_MEDIA_UNAVAILABLE';

    protected int $status = 404;

    public function __construct(string $message = 'No media is available for this lesson.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
