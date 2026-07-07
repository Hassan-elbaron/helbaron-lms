<?php

namespace App\Domains\Learning\Services;

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Domains\Identity\Models\User;
use App\Domains\Learning\Enums\LessonProgressStatus;
use App\Domains\Learning\Models\Enrollment;
use App\Domains\Learning\Models\LessonProgress;
use App\Platform\Shared\Services\BaseService;
use Illuminate\Support\Collection;

/**
 * Computes the "next lesson to resume" for a learner's active enrollments, ordered by recent
 * activity. Read-only.
 */
class ContinueLearningService extends BaseService
{
    /** @return Collection<int, array{enrollment: Enrollment, next_lesson: ?Lesson}> */
    public function forUser(User $user): Collection
    {
        return Enrollment::query()
            ->where('user_id', $user->id)
            ->active()
            ->with('course')
            ->get()
            ->map(fn (Enrollment $enrollment) => [
                'enrollment' => $enrollment,
                'next_lesson' => $this->nextLesson($enrollment),
            ])
            ->sortByDesc(fn ($row) => optional($row['enrollment']->updated_at)->getTimestamp())
            ->values();
    }

    public function nextLesson(Enrollment $enrollment): ?Lesson
    {
        $sectionIds = Section::where('course_id', $enrollment->course_id)
            ->published()->orderBy('position')->pluck('id');

        $lessons = Lesson::whereIn('section_id', $sectionIds)
            ->published()
            ->orderBy('section_id')
            ->orderBy('position')
            ->get();

        $completedIds = LessonProgress::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('status', LessonProgressStatus::Completed->value)
            ->pluck('lesson_id')
            ->flip();

        return $lessons->first(fn (Lesson $lesson) => ! $completedIds->has($lesson->id));
    }
}
