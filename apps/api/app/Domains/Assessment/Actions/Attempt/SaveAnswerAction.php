<?php

namespace App\Domains\Assessment\Actions\Attempt;

use App\Domains\Assessment\Models\AssessmentAnswer;
use App\Domains\Assessment\Models\AssessmentAttempt;
use App\Domains\Assessment\Models\AssessmentQuestion;
use Illuminate\Validation\ValidationException;

/**
 * Records (or replaces) the learner's response to one question. Never grades — grading happens
 * once, at submission, so a learner cannot probe for the correct answer by watching a score change.
 */
class SaveAnswerAction
{
    /** @param  array<string, mixed>|null  $response */
    public function execute(AssessmentAttempt $attempt, string $questionPublicId, ?array $response): AssessmentAnswer
    {
        if (! $attempt->acceptsAnswers()) {
            throw ValidationException::withMessages([
                'attempt' => 'This attempt is no longer accepting answers.',
            ]);
        }

        // The question must be one this sitting actually served. Checking the frozen order — not
        // just the assessment — stops a learner submitting answers to questions that were shuffled
        // out of their paper, which would otherwise inflate their score.
        if (! in_array($questionPublicId, $attempt->questionOrder(), true)) {
            throw ValidationException::withMessages([
                'question' => 'That question is not part of this attempt.',
            ]);
        }

        $question = AssessmentQuestion::query()
            ->where('assessment_id', $attempt->assessment_id)
            ->where('public_id', $questionPublicId)
            ->firstOrFail();

        return AssessmentAnswer::updateOrCreate(
            ['attempt_id' => $attempt->id, 'question_id' => $question->id],
            [
                'response' => $response,
                // Explicitly clear any prior grade: an answer that changes must not keep a stale
                // score attached to it.
                'is_correct' => null,
                'points_awarded' => null,
                'graded_at' => null,
            ],
        );
    }
}
