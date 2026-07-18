<?php

namespace App\Domains\Assessment\Grading\Graders;

use App\Domains\Assessment\Enums\QuestionType;
use App\Domains\Assessment\Grading\Contracts\QuestionGrader;
use App\Domains\Assessment\Grading\GradeResult;
use App\Domains\Assessment\Models\AssessmentAnswer;
use App\Domains\Assessment\Models\AssessmentQuestion;

/**
 * Exactly one option may be selected, and it must be the correct one.
 *
 * Selecting several options is incorrect rather than an error: a client that lets the learner
 * multi-select a single-choice question is buggy, but the learner should not get a 500 for it.
 */
class SingleChoiceGrader implements QuestionGrader
{
    public function type(): QuestionType
    {
        return QuestionType::SingleChoice;
    }

    public function grade(AssessmentQuestion $question, AssessmentAnswer $answer): GradeResult
    {
        $selected = $answer->selectedOptionIds();

        if (count($selected) !== 1) {
            return GradeResult::incorrect();
        }

        $correctIds = $question->options
            ->where('is_correct', true)
            ->pluck('public_id')
            ->all();

        return in_array($selected[0], $correctIds, true)
            ? GradeResult::correct()
            : GradeResult::incorrect();
    }
}
