<?php

namespace App\Domains\Assessment\Analytics;

use App\Domains\Assessment\Models\AssessmentAttempt;
use App\Platform\Shared\Assessment\Contracts\AssessmentStatsPort;
use App\Platform\Shared\Assessment\Data\AssessmentPassRate;

/**
 * Assessment's implementation of the reporting port. Owns the only query outside this domain's
 * own surfaces that reads attempt outcomes, so the coupling is one auditable place.
 */
class AssessmentStatsAdapter implements AssessmentStatsPort
{
    public function passRateForLessons(array $lessonIds): AssessmentPassRate
    {
        if ($lessonIds === []) {
            return AssessmentPassRate::empty();
        }

        // `passed` is null until an attempt is graded, and null on an assessment with no pass mark.
        // Filtering on NOT NULL therefore excludes both — an attempt with no pass/fail outcome
        // cannot contribute to a pass rate in either direction.
        $row = AssessmentAttempt::query()
            ->whereIn('lesson_id', $lessonIds)
            ->whereNotNull('passed')
            ->toBase()
            ->selectRaw('count(*) as graded')
            ->selectRaw('coalesce(sum(case when passed then 1 else 0 end), 0) as passed')
            ->first();

        return new AssessmentPassRate(
            gradedAttempts: (int) ($row->graded ?? 0),
            passedAttempts: (int) ($row->passed ?? 0),
        );
    }
}
