<?php

namespace App\Domains\Catalog\Contracts;

use App\Domains\Catalog\Models\Course;
use App\Platform\Shared\Publishing\Data\CourseReadinessInput;
use App\Platform\Shared\Publishing\Data\ReadinessReport;

/**
 * Default guard: always allows publishing. Replaced by a downstream binding when curriculum
 * validation exists.
 */
class NullCoursePublishGuard implements CoursePublishGuard
{
    public function canPublish(Course $course): bool
    {
        return true;
    }

    public function reason(): ?string
    {
        return null;
    }

    /**
     * An empty report, which scores 100 — consistent with a guard that permits everything. It is
     * NOT a report claiming checks passed: Catalog alone knows of no checks to run, and inventing
     * passed entries here would misrepresent an unevaluated course as a vetted one.
     */
    public function report(CourseReadinessInput $course): ReadinessReport
    {
        return new ReadinessReport([], [], now()->toIso8601String());
    }
}
