<?php

namespace App\Domains\Catalog\Contracts;

use App\Domains\Catalog\Models\Course;
use App\Platform\Shared\Publishing\Data\CourseReadinessInput;
use App\Platform\Shared\Publishing\Data\ReadinessReport;

/**
 * Inbound extension point Catalog owns. A downstream domain (e.g. Authoring) may bind an
 * implementation that vetoes publishing a course with an invalid/empty curriculum. Catalog
 * ships a permissive default (NullCoursePublishGuard) so it works standalone.
 *
 * `report()` exists so the readiness panel and the guard share one evaluation. An implementation
 * MUST derive `canPublish()` from its own report rather than checking separately — a panel that
 * says "ready" while the guard refuses is worse than no panel, because it trains authors to ignore
 * it.
 */
interface CoursePublishGuard
{
    /** Return true if the course may be published. */
    public function canPublish(Course $course): bool;

    /** Human-readable reason when canPublish() is false. */
    public function reason(): ?string;

    /**
     * The full, explainable evaluation behind canPublish().
     *
     * Takes the flattened input rather than a Course because the implementing domain may not be
     * permitted to depend on Catalog; Catalog owns the mapping from its own model.
     */
    public function report(CourseReadinessInput $course): ReadinessReport;
}
