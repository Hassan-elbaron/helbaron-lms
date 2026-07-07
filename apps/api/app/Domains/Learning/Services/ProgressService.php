<?php

namespace App\Domains\Learning\Services;

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Domains\Learning\Enums\EnrollmentStatus;
use App\Domains\Learning\Enums\LessonProgressStatus;
use App\Domains\Learning\Models\Enrollment;
use App\Domains\Learning\Models\LessonProgress;
use App\Platform\Shared\Services\BaseService;
use Illuminate\Support\Collection;

/**
 * Records lesson progress and recomputes section/course percentages. Idempotent per lesson:
 * re-recording the same completion is a no-op. Completion detection is derived, not asserted.
 */
class ProgressService extends BaseService
{
    /**
     * @return array{progress: LessonProgress, enrollment: Enrollment, just_completed_lesson: bool, just_completed_course: bool}
     */
    public function record(Enrollment $enrollment, Lesson $lesson, LessonProgressStatus $status, ?int $positionSeconds = null): array
    {
        $progress = LessonProgress::firstOrNew([
            'enrollment_id' => $enrollment->id,
            'lesson_id' => $lesson->id,
        ]);

        $wasCompleted = $progress->exists && $progress->status === LessonProgressStatus::Completed;

        $progress->status = $status;
        if ($positionSeconds !== null) {
            $progress->position_seconds = $positionSeconds;
        }
        if ($status === LessonProgressStatus::Completed && $progress->completed_at === null) {
            $progress->completed_at = now();
        }
        $progress->save();

        $justCompletedLesson = ! $wasCompleted && $status === LessonProgressStatus::Completed;

        $justCompletedCourse = $this->recomputeCoursePercentage($enrollment);

        return [
            'progress' => $progress,
            'enrollment' => $enrollment->refresh(),
            'just_completed_lesson' => $justCompletedLesson,
            'just_completed_course' => $justCompletedCourse,
        ];
    }

    /** Published lesson ids for a course, grouped and flat, used for percentage math. */
    public function publishedLessonIds(int $courseId): Collection
    {
        $sectionIds = Section::where('course_id', $courseId)->published()->pluck('id');

        return Lesson::whereIn('section_id', $sectionIds)->published()->pluck('id');
    }

    public function sectionPercentage(Enrollment $enrollment, Section $section): int
    {
        $lessonIds = Lesson::where('section_id', $section->id)->published()->pluck('id');

        return $this->percentage($enrollment, $lessonIds);
    }

    private function recomputeCoursePercentage(Enrollment $enrollment): bool
    {
        $lessonIds = $this->publishedLessonIds($enrollment->course_id);
        $percentage = $this->percentage($enrollment, $lessonIds);

        $justCompleted = false;

        $enrollment->progress_percentage = $percentage;

        if ($percentage >= (int) config('learning.progress.completion_percentage', 100)
            && $enrollment->status !== EnrollmentStatus::Completed
            && $lessonIds->isNotEmpty()) {
            $enrollment->status = EnrollmentStatus::Completed;
            $enrollment->completed_at = now();
            $justCompleted = true;
        }

        $enrollment->save();

        return $justCompleted;
    }

    private function percentage(Enrollment $enrollment, Collection $lessonIds): int
    {
        if ($lessonIds->isEmpty()) {
            return 0;
        }

        $completed = LessonProgress::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereIn('lesson_id', $lessonIds)
            ->where('status', LessonProgressStatus::Completed->value)
            ->count();

        return (int) floor(($completed / $lessonIds->count()) * 100);
    }
}
