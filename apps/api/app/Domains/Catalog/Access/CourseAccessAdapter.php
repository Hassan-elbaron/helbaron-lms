<?php

namespace App\Domains\Catalog\Access;

use App\Domains\Catalog\Models\Course;
use App\Platform\Identity\Contracts\Actor;
use App\Platform\Identity\Contracts\CourseAccessPort;
use Illuminate\Support\Facades\Gate;

/**
 * Catalog-side implementation of CourseAccessPort. Lives here because Catalog owns the Course
 * model — importing it anywhere else would be a layer violation.
 *
 * It deliberately does NOT restate the ownership rule (super_admin/admin bypass, assigned trainer,
 * course not archived). It delegates to the `authoring.manage-curriculum` gate, which is the single
 * definition of that rule in the codebase. The reference is a gate NAME, not a class, so no
 * compile-time dependency on Authoring is created — and if that gate's definition changes, every
 * consumer of this port changes with it automatically.
 *
 * An undefined gate denies, so this is fail-closed if Authoring's provider is ever unloaded.
 */
class CourseAccessAdapter implements CourseAccessPort
{
    /** Owned and defined by App\Domains\Authoring\Providers\AuthoringServiceProvider. */
    private const MANAGE_CURRICULUM = 'authoring.manage-curriculum';

    public function canManageContent(Actor $actor, int $courseId): bool
    {
        $course = Course::query()->find($courseId);

        if ($course === null) {
            return false;
        }

        return Gate::forUser($actor)->allows(self::MANAGE_CURRICULUM, $course);
    }

    public function manageableCourseId(Actor $actor, string $coursePublicId): ?int
    {
        $course = Course::query()->where('public_id', $coursePublicId)->first();

        if ($course === null || ! Gate::forUser($actor)->allows(self::MANAGE_CURRICULUM, $course)) {
            return null;
        }

        return (int) $course->id;
    }
}
