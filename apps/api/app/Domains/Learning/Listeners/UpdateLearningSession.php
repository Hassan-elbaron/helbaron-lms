<?php

namespace App\Domains\Learning\Listeners;

use App\Domains\Learning\Events\LessonProgressRecorded;
use App\Domains\Learning\Models\LearningSession;

/**
 * Keeps the per-(user, course) "resume" pointer fresh whenever progress is recorded.
 */
class UpdateLearningSession
{
    public function handle(LessonProgressRecorded $event): void
    {
        LearningSession::updateOrCreate(
            [
                'user_id' => $event->enrollment->user_id,
                'course_id' => $event->enrollment->course_id,
            ],
            [
                'last_lesson_id' => $event->lesson->id,
                'last_activity_at' => now(),
            ],
        );
    }
}
