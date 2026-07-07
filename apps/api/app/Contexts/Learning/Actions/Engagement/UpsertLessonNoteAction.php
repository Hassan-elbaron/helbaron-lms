<?php

namespace App\Contexts\Learning\Actions\Engagement;

use App\Domains\Authoring\Models\Lesson;
use App\Platform\Identity\Models\User;
use App\Contexts\Learning\Models\LessonNote;
use App\Platform\Shared\Actions\BaseAction;

class UpsertLessonNoteAction extends BaseAction
{
    public function execute(User $user, Lesson $lesson, string $body): LessonNote
    {
        return $this->transaction(function () use ($user, $lesson, $body): LessonNote {
            return LessonNote::updateOrCreate(
                ['user_id' => $user->id, 'lesson_id' => $lesson->id],
                ['body' => $body],
            );
        });
    }
}
