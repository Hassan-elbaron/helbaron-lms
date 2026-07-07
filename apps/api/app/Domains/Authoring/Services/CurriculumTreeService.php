<?php

namespace App\Domains\Authoring\Services;

use App\Domains\Authoring\Models\Section;
use App\Domains\Catalog\Models\Course;
use App\Platform\Shared\Services\BaseService;
use Illuminate\Support\Collection;

/**
 * Assembles the ordered section→lesson tree for a course. Read-only projection over the
 * authoring tables; callers decide whether to request published-only content.
 */
class CurriculumTreeService extends BaseService
{
    public function forCourse(Course $course, bool $publishedOnly = false): Collection
    {
        $query = Section::query()
            ->where('course_id', $course->id)
            ->with(['lessons' => function ($q) use ($publishedOnly): void {
                $q->with('media')->orderBy('position');
                if ($publishedOnly) {
                    $q->published();
                }
            }])
            ->orderBy('position');

        if ($publishedOnly) {
            $query->published();
        }

        return $query->get();
    }
}
