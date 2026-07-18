<?php

namespace App\Domains\Assessment\Grading;

/**
 * What a grader returns for ONE answer.
 *
 * Graders deliberately return a CREDIT RATIO (0.0–1.0), not a point total. Converting ratio to
 * points — and applying negative marking — is the scoring service's job, so per-question weighting
 * and penalty policy can change without touching a single grader.
 *
 * `requiresManualReview` is how a future Essay/AI grader declines to auto-score: it returns
 * `pending()`, the attempt lands in AttemptStatus::AwaitingReview, and no existing code changes.
 */
final readonly class GradeResult
{
    private function __construct(
        public ?bool $isCorrect,
        /** Fraction of the question's points earned, 0.0–1.0. */
        public float $ratio,
        public bool $requiresManualReview,
    ) {}

    public static function correct(): self
    {
        return new self(true, 1.0, false);
    }

    public static function incorrect(): self
    {
        return new self(false, 0.0, false);
    }

    /**
     * Partial credit. A ratio of 0 is recorded as incorrect and a ratio of 1 as fully correct, so
     * callers never have to special-case the boundaries.
     */
    public static function partial(float $ratio): self
    {
        $clamped = max(0.0, min(1.0, $ratio));

        return new self($clamped > 0.0, $clamped, false);
    }

    /** Not machine-gradable — awaits a human. Scores nothing until then. */
    public static function pending(): self
    {
        return new self(null, 0.0, true);
    }

    public function isFullyCorrect(): bool
    {
        return $this->isCorrect === true && $this->ratio >= 1.0;
    }

    /** True when the learner earned nothing — the only case a penalty may apply. */
    public function earnedNothing(): bool
    {
        return ! $this->requiresManualReview && $this->ratio <= 0.0;
    }
}
