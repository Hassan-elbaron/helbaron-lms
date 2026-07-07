<?php

namespace App\Domains\Authoring\Actions\Section;

use App\Domains\Authoring\Models\Section;
use App\Domains\Catalog\Models\Course;
use App\Platform\Shared\Actions\BaseAction;

class ReorderSectionsAction extends BaseAction
{
    /** @param array<int, string> $orderedPublicIds */
    public function execute(Course $course, array $orderedPublicIds): void
    {
        $this->transaction(function () use ($course, $orderedPublicIds): void {
            foreach ($orderedPublicIds as $position => $publicId) {
                Section::where('course_id', $course->id)
                    ->where('public_id', $publicId)
                    ->update(['position' => $position]);
            }
        });
    }
}
