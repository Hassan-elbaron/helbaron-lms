<?php

namespace App\Contexts\Learning\Actions\Progress;

use App\Domains\Authoring\Models\Lesson;
use App\Platform\Identity\Models\User;
use App\Contexts\Learning\Enums\LessonProgressStatus;
use App\Contexts\Learning\Events\CourseCompleted;
use App\Contexts\Learning\Events\LessonCompleted;
use App\Contexts\Learning\Events\LessonProgressRecorded;
use App\Contexts\Learning\Models\LessonProgress;
use App\Contexts\Learning\Services\LessonAccessService;
use App\Contexts\Learning\Services\ProgressService;
use App\Platform\Shared\Actions\BaseAction;

/**
 * Records progress for a lesson. Idempotent per lesson; recomputes completion and dispatches
 * events after commit. Requires access (enrollment + prerequisites) via LessonAccessService.
 */
class RecordLessonProgressAction extends BaseAction
{
    public function __construct(
        private readonly LessonAccessService $access,
        private readonly ProgressService $progress,
    ) {}

    public function execute(User $user, Lesson $lesson, LessonProgressStatus $status, ?int $positionSeconds = null): LessonProgress
    {
        $enrollment = $this->access->assertAccess($user, $lesson);

        $result = $this->transaction(fn () => $this->progress->record($enrollment, $lesson, $status, $positionSeconds));

        LessonProgressRecorded::dispatch($result['enrollment'], $lesson);

        if ($result['just_completed_lesson']) {
            LessonCompleted::dispatch($result['enrollment'], $lesson);
        }

        if ($result['just_completed_course']) {
            CourseCompleted::dispatch($result['enrollment']);
        }

        return $result['progress'];
    }
}
