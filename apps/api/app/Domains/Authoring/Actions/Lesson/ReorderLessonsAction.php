<?php

namespace App\Domains\Authoring\Actions\Lesson;

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Shared\Actions\BaseAction;

class ReorderLessonsAction extends BaseAction
{
    /** @param array<int, string> $orderedPublicIds */
    public function execute(Section $section, array $orderedPublicIds): void
    {
        $this->transaction(function () use ($section, $orderedPublicIds): void {
            foreach ($orderedPublicIds as $position => $publicId) {
                Lesson::where('section_id', $section->id)
                    ->where('public_id', $publicId)
                    ->update(['position' => $position]);
            }
        });
    }
}
