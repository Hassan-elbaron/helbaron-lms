<?php

namespace App\Domains\Authoring\Actions\Lesson;

use App\Domains\Authoring\Models\Lesson;
use App\Platform\Shared\Actions\BaseAction;

class UpdateLessonAction extends BaseAction
{
    /** @param array<string, mixed> $data */
    public function execute(Lesson $lesson, array $data): Lesson
    {
        return $this->transaction(function () use ($lesson, $data): Lesson {
            $lesson->fill(array_filter([
                'title' => $data['title'] ?? null,
                'type' => $data['type'] ?? null,
                'content' => $data['content'] ?? null,
            ], fn ($v) => $v !== null));
            $lesson->save();

            return $lesson;
        });
    }
}
