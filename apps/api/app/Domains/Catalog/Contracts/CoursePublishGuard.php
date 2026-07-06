<?php

namespace App\Domains\Catalog\Contracts;

use App\Domains\Catalog\Models\Course;

/**
 * Inbound extension point Catalog owns. A downstream domain (e.g. Authoring) may bind an
 * implementation that vetoes publishing a course with an invalid/empty curriculum. Catalog
 * ships a permissive default (NullCoursePublishGuard) so it works standalone.
 */
interface CoursePublishGuard
{
    /** Return true if the course may be published. */
    public function canPublish(Course $course): bool;

    /** Human-readable reason when canPublish() is false. */
    public function reason(): ?string;
}
