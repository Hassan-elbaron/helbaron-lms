<?php

namespace App\Domains\Authoring\Actions\Curriculum;

use App\Domains\Authoring\Events\CurriculumReordered;
use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Domains\Catalog\Models\Course;
use App\Shared\Actions\BaseAction;

/**
 * Applies a full drag-and-drop reorder of a course's curriculum in one transaction:
 * section order + per-section lesson order (and lesson re-parenting across sections).
 */
class ReorderCurriculumAction extends BaseAction
{
    /**
     * @param  array<int, array{id: string, lessons?: array<int, string>}>  $tree
     */
    public function execute(Course $course, array $tree): void
    {
        $this->transaction(function () use ($course, $tree): void {
            foreach ($tree as $sectionPosition => $node) {
                $section = Section::where('course_id', $course->id)
                    ->where('public_id', $node['id'])
                    ->first();

                if ($section === null) {
                    continue;
                }

                $section->update(['position' => $sectionPosition]);

                foreach ($node['lessons'] ?? [] as $lessonPosition => $lessonPublicId) {
                    Lesson::where('public_id', $lessonPublicId)->update([
                        'section_id' => $section->id,
                        'position' => $lessonPosition,
                    ]);
                }
            }
        });

        CurriculumReordered::dispatch($course);
    }
}
