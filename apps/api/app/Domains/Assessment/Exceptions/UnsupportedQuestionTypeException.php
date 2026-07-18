<?php

namespace App\Domains\Assessment\Exceptions;

use App\Domains\Assessment\Enums\QuestionType;
use RuntimeException;

/**
 * Thrown when a question's type has no registered grader. This is a programming/deployment error,
 * not user input — FormRequests reject unknown types long before persistence, and the publish
 * guard refuses to publish an assessment containing an ungradable question.
 */
class UnsupportedQuestionTypeException extends RuntimeException
{
    public function __construct(public readonly QuestionType $type)
    {
        parent::__construct("No grader is registered for question type \"{$type->value}\".");
    }
}
