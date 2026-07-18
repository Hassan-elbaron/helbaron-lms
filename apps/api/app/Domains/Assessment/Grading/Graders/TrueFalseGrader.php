<?php

namespace App\Domains\Assessment\Grading\Graders;

use App\Domains\Assessment\Enums\QuestionType;

/**
 * True/False is single-choice over a fixed two-option set. It gets its own grader (rather than
 * being aliased to single_choice) purely so the registry stays a 1:1 type→grader map — but the
 * scoring rule is identical, so it is inherited rather than duplicated.
 */
class TrueFalseGrader extends SingleChoiceGrader
{
    public function type(): QuestionType
    {
        return QuestionType::TrueFalse;
    }
}
