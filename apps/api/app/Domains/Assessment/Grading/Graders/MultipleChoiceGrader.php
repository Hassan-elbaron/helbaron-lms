<?php

namespace App\Domains\Assessment\Grading\Graders;

use App\Domains\Assessment\Enums\QuestionType;
use App\Domains\Assessment\Grading\Contracts\QuestionGrader;
use App\Domains\Assessment\Grading\GradeResult;
use App\Domains\Assessment\Models\AssessmentAnswer;
use App\Domains\Assessment\Models\AssessmentQuestion;

/**
 * Select-all-that-apply.
 *
 * Default is all-or-nothing: the selected set must equal the correct set exactly. Authors can opt
 * into partial credit per question with `config: {"partial_credit": true}`, which scores
 * (correct hits − wrong hits) / total correct, floored at zero. That formula is deliberate — without
 * subtracting wrong selections, a learner could select every option and score full marks.
 *
 * Note this is a `config` read, not a branch on question type: the registry stays 1:1.
 */
class MultipleChoiceGrader implements QuestionGrader
{
    public function type(): QuestionType
    {
        return QuestionType::MultipleChoice;
    }

    public function grade(AssessmentQuestion $question, AssessmentAnswer $answer): GradeResult
    {
        $correctIds = $question->options->where('is_correct', true)->pluck('public_id')->all();

        if ($correctIds === []) {
            // A question with no key cannot be answered correctly; publishing guards against this.
            return GradeResult::incorrect();
        }

        // Deduplicate: a repeated id in the payload must not count twice.
        $selected = array_values(array_unique($answer->selectedOptionIds()));

        $hits = count(array_intersect($selected, $correctIds));
        $misses = count(array_diff($selected, $correctIds));

        if ($question->setting('partial_credit', false) === true) {
            return GradeResult::partial(($hits - $misses) / count($correctIds));
        }

        return $hits === count($correctIds) && $misses === 0
            ? GradeResult::correct()
            : GradeResult::incorrect();
    }
}
