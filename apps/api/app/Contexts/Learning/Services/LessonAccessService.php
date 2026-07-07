<?php

namespace App\Contexts\Learning\Services;

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Platform\Identity\Models\User;
use App\Contexts\Learning\Exceptions\LessonLockedException;
use App\Contexts\Learning\Exceptions\NotEnrolledException;
use App\Contexts\Learning\Models\Enrollment;
use App\Contexts\Learning\Models\LessonProgress;
use App\Platform\Shared\Services\BaseService;

/**
 * Central access rule for lessons: preview lessons are open; otherwise the user must have an
 * active enrollment in the lesson's course AND have completed the lesson's prerequisites.
 */
class LessonAccessService extends BaseService
{
    public function courseIdForLesson(Lesson $lesson): int
    {
        return (int) Section::whereKey($lesson->section_id)->value('course_id');
    }

    public function activeEnrollment(User $user, int $courseId): ?Enrollment
    {
        return Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->active()
            ->first();
    }

    public function canAccess(User $user, Lesson $lesson): bool
    {
        try {
            $this->assertAccess($user, $lesson);

            return true;
        } catch (NotEnrolledException|LessonLockedException) {
            return false;
        }
    }

    /** Throws NotEnrolledException or LessonLockedException when access is denied. */
    public function assertAccess(User $user, Lesson $lesson): Enrollment
    {
        $courseId = $this->courseIdForLesson($lesson);
        $enrollment = $this->activeEnrollment($user, $courseId);

        // Preview lessons are viewable, but still need an enrollment context for progress.
        if ($enrollment === null) {
            throw new NotEnrolledException;
        }

        if (! $lesson->is_preview && ! $this->prerequisitesMet($enrollment, $lesson)) {
            throw new LessonLockedException;
        }

        return $enrollment;
    }

    private function prerequisitesMet(Enrollment $enrollment, Lesson $lesson): bool
    {
        $prerequisiteIds = $lesson->prerequisites()->pluck('lessons.id');

        if ($prerequisiteIds->isEmpty()) {
            return true;
        }

        $completed = LessonProgress::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereIn('lesson_id', $prerequisiteIds)
            ->where('status', 'completed')
            ->count();

        return $completed === $prerequisiteIds->count();
    }
}
