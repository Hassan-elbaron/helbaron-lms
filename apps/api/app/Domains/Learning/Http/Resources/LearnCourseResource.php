<?php

namespace App\Domains\Learning\Http\Resources;

use App\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * Learner-facing course view: metadata + progress + the curriculum tree with per-lesson
 * completion/lock flags. No media identifiers.
 */
class LearnCourseResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $course = $this->resource['course'];
        $enrollment = $this->resource['enrollment'];

        return [
            'course' => [
                'id' => $course->public_id,
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
