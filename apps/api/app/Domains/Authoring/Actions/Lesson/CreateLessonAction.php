<?php

namespace App\Domains\Authoring\Actions\Lesson;

use App\Domains\Authoring\Enums\LessonType;
use App\Domains\Authoring\Enums\PublishState;
use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Shared\Actions\BaseAction;

class CreateLessonAction extends BaseAction
{
    /** @param array<string, mixed> $data */
    public function execute(Section $section, array $data): Lesson
    {
        return $this->transaction(function () use ($section, $data): Lesson {
            $position = (int) Lesson::where('section_id', $section->id)->max('position');

            return Lesson::create([
                'section_id' => $section->id,
                'title' => $data['title'],
                'type' => ($data['type'] instanceof LessonType ? $data['type']->value : $data['type']),
                'content' => $data['content'] ?? [],
                'position' => $position + 1,
                'is_preview' => $data['is_preview'] ?? config('authoring.preview.default', false),
                'publish_state' => PublishState::Draft->value,
            ]);
        });
    }
}
