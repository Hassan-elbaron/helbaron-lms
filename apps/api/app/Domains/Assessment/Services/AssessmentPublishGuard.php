<?php

namespace App\Domains\Assessment\Services;

use App\Domains\Assessment\Grading\GraderRegistry;
use App\Domains\Assessment\Models\Assessment;
use Illuminate\Validation\ValidationException;

/**
 * Refuses to publish an assessment a learner could not fairly sit.
 *
 * Publishing is the point of no return: once published, learners begin attempts and those attempts
 * become permanent records. Every check here exists because the alternative is a learner losing
 * marks to an authoring mistake.
 */
class AssessmentPublishGuard
{
    public function __construct(private readonly GraderRegistry $registry) {}

    /** @throws ValidationException */
    public function assertPublishable(Assessment $assessment): void
    {
        $questions = $assessment->questions()->withCount('correctOptions')->get();

        if ($questions->isEmpty()) {
            $this->fail('An assessment needs at least one question before it can be published.');
        }

        foreach ($questions as $question) {
            // Guards against a question authored by an older/newer build than the one deploying.
            if (! $this->registry->supports($question->type)) {
                $this->fail("This build cannot grade \"{$question->type->value}\" questions, so the assessment cannot be published.");
            }

            // A question with no key is unanswerable — every learner would lose its marks.
            if ((int) $question->correct_options_count === 0) {
                $this->fail("Question {$question->position} has no correct answer.");
            }

            if ((float) $question->points <= 0) {
                $this->fail("Question {$question->position} must be worth more than zero points.");
            }
        }

        // Serving more questions than exist would silently shrink the paper.
        $perAttempt = $assessment->questions_per_attempt;
        if ($perAttempt !== null && $perAttempt > $questions->count()) {
            $this->fail('This assessment is set to serve more questions per attempt than it contains.');
        }

        if ($assessment->passing_score !== null && $assessment->passing_score > 100) {
            $this->fail('The passing score cannot exceed 100%.');
        }
    }

    /** @throws ValidationException */
    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['status' => $message]);
    }
}
