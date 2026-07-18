<?php

namespace App\Domains\Assessment\Actions\Question;

use App\Domains\Assessment\Models\AssessmentQuestion;

/**
 * Soft-deletes a question. Answers from attempts already sat point at it, so the row is retained
 * and simply stops being served on new attempts.
 */
class DeleteQuestionAction
{
    public function execute(AssessmentQuestion $question): void
    {
        $question->delete();
    }
}
