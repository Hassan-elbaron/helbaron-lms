<?php

namespace App\Domains\Learning\Actions\Engagement;

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Identity\Models\User;
use App\Domains\Learning\Models\LessonNote;
use App\Shared\Actions\BaseAction;

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
