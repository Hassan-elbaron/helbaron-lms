<?php

namespace App\Domains\Catalog\Contracts;

use App\Domains\Catalog\Models\Course;

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
}
