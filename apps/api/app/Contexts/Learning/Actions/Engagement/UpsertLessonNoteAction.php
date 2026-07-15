<?php

namespace App\Contexts\Learning\Actions\Engagement;

use App\Contexts\Learning\Models\LessonNote;
use App\Platform\Shared\Actions\BaseAction;

class UpsertLessonNoteAction extends BaseAction
{
    public function executeByUserId(int $userId, int $lessonId, string $body): LessonNote
    {
        return $this->transaction(function () use ($userId, $lessonId, $body): LessonNote {
            return LessonNote::updateOrCreate(
                ['user_id' => $userId, 'lesson_id' => $lessonId],
                ['body' => $body],
            );
        });
    }
}
