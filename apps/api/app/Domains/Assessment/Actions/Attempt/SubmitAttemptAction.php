<?php

namespace App\Domains\Assessment\Actions\Attempt;

use App\Domains\Assessment\Enums\AttemptStatus;
use App\Domains\Assessment\Grading\AttemptScorer;
use App\Domains\Assessment\Models\AssessmentAttempt;
use Illuminate\Validation\ValidationException;

/**
 * Closes an attempt and scores it.
 *
 * An expired attempt is still submittable: the learner ran out of time, which means their saved
 * answers should be graded, not discarded. The attempt is simply marked Expired rather than
 * Submitted so the record shows what happened.
 */
class SubmitAttemptAction
{
    public function __construct(private readonly AttemptScorer $scorer) {}

    public function execute(AssessmentAttempt $attempt): AssessmentAttempt
    {
        if ($attempt->status->isFinal()) {
            throw ValidationException::withMessages([
                'attempt' => 'This attempt has already been closed.',
            ]);
        }

        $attempt->forceFill([
            'status' => $attempt->hasExpired()
                ? AttemptStatus::Expired->value
                : AttemptStatus::Submitted->value,
            'submitted_at' => now(),
        ])->save();

        // The scorer sets the final status (Graded, or AwaitingReview once manually-graded types
        // exist), so submission and grading stay one atomic step from the caller's perspective.
        return $this->scorer->score($attempt);
    }
}
