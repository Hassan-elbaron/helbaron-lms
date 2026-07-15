<?php

namespace App\Contexts\Learning\Services;

use App\Contexts\Learning\Exceptions\LessonLockedException;
use App\Contexts\Learning\Exceptions\NotEnrolledException;
use App\Contexts\Learning\Models\Enrollment;
use App\Contexts\Learning\Models\LessonProgress;
use App\Platform\Shared\Curriculum\Contracts\CurriculumReadPort;
use App\Platform\Shared\Services\BaseService;

/**
 * Central access rule for lessons: preview lessons are open (but still need an enrollment context);
 * otherwise the user must have an active enrollment in the lesson's course AND have completed the
 * lesson's prerequisites. All curriculum reads (course-of-lesson, isPreview, prerequisites) go
 * through CurriculumReadPort — no Authoring/Catalog model dependency.
 */
class LessonAccessService extends BaseService
{
    public function __construct(private readonly CurriculumReadPort $curriculum) {}

    public function courseIdForLessonId(int $lessonId): int
    {
        return $this->curriculum->courseIdForLesson($lessonId) ?? 0;
    }

    public function activeEnrollmentByUserId(int $userId, int $courseId): ?Enrollment
    {
        return Enrollment::query()
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->active()
            ->first();
    }

    public function canAccessByUserId(int $userId, int $lessonId): bool
    {
        try {
            $this->assertAccessByUserId($userId, $lessonId);

            return true;
        } catch (NotEnrolledException|LessonLockedException) {
            return false;
        }
    }

    /** Throws NotEnrolledException or LessonLockedException when access is denied. */
    public function assertAccessByUserId(int $userId, int $lessonId): Enrollment
    {
        $ref = $this->curriculum->lessonRefById($lessonId);
        $enrollment = $this->activeEnrollmentByUserId($userId, $ref?->courseId ?? 0);

        // Preview lessons are viewable, but still need an enrollment context for progress.
        if ($enrollment === null) {
            throw new NotEnrolledException;
        }

        if ($ref !== null && ! $ref->isPreview && ! $this->prerequisitesMetByIds($enrollment, $ref->prerequisiteLessonIds)) {
            throw new LessonLockedException;
        }

        return $enrollment;
    }

    /** @param list<int> $prerequisiteIds */
    private function prerequisitesMetByIds(Enrollment $enrollment, array $prerequisiteIds): bool
    {
        if ($prerequisiteIds === []) {
            return true;
        }

        $completed = LessonProgress::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereIn('lesson_id', $prerequisiteIds)
            ->where('status', 'completed')
            ->count();

        return $completed === count($prerequisiteIds);
    }
}
