<?php

namespace App\Contexts\Learning\Actions\Engagement;

use App\Contexts\Learning\Models\LessonBookmark;
use App\Platform\Shared\Actions\BaseAction;

class ToggleBookmarkAction extends BaseAction
{
    /** @return array{bookmarked: bool} */
    public function executeByUserId(int $userId, int $lessonId): array
    {
        return $this->transaction(function () use ($userId, $lessonId): array {
            $existing = LessonBookmark::where('user_id', $userId)->where('lesson_id', $lessonId)->first();

            if ($existing !== null) {
                $existing->delete();

                return ['bookmarked' => false];
            }

            LessonBookmark::create(['user_id' => $userId, 'lesson_id' => $lessonId]);

            return ['bookmarked' => true];
        });
    }
}
