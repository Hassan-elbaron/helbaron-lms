<?php

namespace App\Domains\Authoring\Exceptions;

class CrossCourseReferenceException extends AuthoringException
{
    protected string $errorCode = 'AUTHORING_CROSS_COURSE_REFERENCE';

    protected int $status = 422;

    public function __construct(string $message = 'Prerequisites must belong to the same course.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
