<?php

namespace App\Domains\Authoring\Actions\Section;

use App\Domains\Authoring\Enums\PublishState;
use App\Domains\Authoring\Models\Section;
use App\Domains\Catalog\Models\Course;
use App\Shared\Actions\BaseAction;

class CreateSectionAction extends BaseAction
{
    /** @param array<string, mixed> $data */
    public function execute(Course $course, array $data): Section
    {
        return $this->transaction(function () use ($course, $data): Section {
            $position = (int) Section::where('course_id', $course->id)->max('position');

            return Section::create([
                'course_id' => $course->id,
                'title' => $data['title'],
                'summary' => $data['summary'] ?? null,
                'position' => $position + 1,
                'publish_state' => PublishState::Draft->value,
            ]);
        });
    }
}
