<?php

namespace App\Contexts\Learning\Http\Resources;

use App\Platform\Shared\Curriculum\Contracts\CurriculumReadPort;
use App\Platform\Shared\Curriculum\Data\CourseRef;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * Learner-facing course view: metadata + progress + the curriculum tree with per-lesson
 * completion/lock flags. No media identifiers. Accepts either a CourseRef + curriculum-tree nodes
 * (Phase 3B DTO input) or Course/Section models (mapped via CurriculumReadPort — the current
 * controller path). Enrollment is the Learning-owned model. Output is identical.
 */
class LearnCourseResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $courseInput = $this->resource['course'];
        $course = $courseInput instanceof CourseRef
            ? $courseInput
            : app(CurriculumReadPort::class)->courseRef($courseInput);

        $enrollment = $this->resource['enrollment'];

        return [
            'course' => [
                'id' => $course->publicId,
                'title' => $course->title,
                'slug' => $course->slug,
            ],
            'enrollment' => [
                'id' => $enrollment->public_id,
                'status' => $enrollment->status->value,
                'progress_percentage' => $enrollment->progress_percentage,
            ],
            'sections' => collect($this->resource['sections'])->map(fn ($section) => LearnSectionResource::make($section)
                ->additional([
                    'completed_ids' => $this->resource['completed_ids'],
                    'accessible_ids' => $this->resource['accessible_ids'],
                ])->resolve()),
        ];
    }
}
