<?php

namespace App\Platform\Shared\Assessment\Data;

/**
 * Aggregate quiz performance for a set of lessons.
 *
 * `passRate` is NULL when no attempt has been graded — deliberately not 0. A course nobody has
 * sat a quiz for has an unknown pass rate, and rendering that as 0% would tell an instructor their
 * learners are failing when in fact none have tried. Callers must surface the null as "no data".
 */
final readonly class AssessmentPassRate
{
    public function __construct(
        public int $gradedAttempts,
        public int $passedAttempts,
    ) {}

    /** Whole-percentage pass rate, or null when nothing has been graded yet. */
    public function passRate(): ?int
    {
        if ($this->gradedAttempts === 0) {
            return null;
        }

        return (int) round(($this->passedAttempts / $this->gradedAttempts) * 100);
    }

    public static function empty(): self
    {
        return new self(0, 0);
    }
}
