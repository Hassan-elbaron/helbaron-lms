<?php

namespace App\Contexts\Learning\Actions\Engagement;

use App\Domains\Authoring\Models\Lesson;
use App\Platform\Identity\Models\User;
use App\Contexts\Learning\Models\LessonBookmark;
use App\Platform\Shared\Actions\BaseAction;

class ToggleBookmarkAction extends BaseAction
{
    /** @return array{bookmarked: bool} */
    public function execute(User $user, Lesson $lesson): array
    {
        return $this->transaction(function () use ($user, $lesson): array {
            $existing = LessonBookmark::where('user_id', $user->id)->where('lesson_id', $lesson->id)->first();

            if ($existing !== null) {
                $existing->delete();

                return ['bookmarked' => false];
            }

            LessonBookmark::create(['user_id' => $user->id, 'lesson_id' => $lesson->id]);

            return ['bookmarked' => true];
        });
    }
}
