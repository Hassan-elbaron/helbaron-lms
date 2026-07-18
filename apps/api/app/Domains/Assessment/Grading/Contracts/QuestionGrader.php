<?php

namespace App\Domains\Assessment\Grading\Contracts;

use App\Domains\Assessment\Enums\QuestionType;
use App\Domains\Assessment\Grading\GradeResult;
use App\Domains\Assessment\Models\AssessmentAnswer;
use App\Domains\Assessment\Models\AssessmentQuestion;

/**
 * Grades one answer to one question.
 *
 * One implementation per QuestionType, resolved through GraderRegistry. This is the seam that
 * keeps grading free of `match ($question->type)` chains: adding Essay, Matching, Ordering,
 * Numeric, Code or an AI-evaluated type means writing a class and registering it — no existing
 * grader, action, controller or migration is touched.
 */
interface QuestionGrader
{
    /** The single type this grader is responsible for. */
    public function type(): QuestionType;

    /**
     * Grade the answer. Implementations MUST tolerate a missing, empty or malformed `response`
     * (an unanswered question is a normal outcome, and payloads are attacker-controlled) and
     * return `GradeResult::incorrect()` rather than throwing.
     */
    public function grade(AssessmentQuestion $question, AssessmentAnswer $answer): GradeResult;
}
