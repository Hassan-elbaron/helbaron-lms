<?php

namespace App\Contexts\Learning\Http\Resources;

use App\Contexts\Learning\Models\Enrollment;
use App\Platform\Shared\Curriculum\Data\CourseRef;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @property Enrollment $resource
 *
 * Phase 3B: the nested course block can be rendered from a CourseRef supplied via
 * `additional(['course_ref' => CourseRef])` (DTO input); otherwise it falls back to the loaded
 * `course` relation exactly as before (byte-identical, including omission when not loaded).
 */
class MyLearningItemResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $courseRef = $this->additional['course_ref'] ?? null;

        return [
            'enrollment_id' => $this->resource->public_id,
            'status' => $this->resource->status->value,
            'progress_percentage' => $this->resource->progress_percentage,
            'enrolled_at' => $this->resource->enrolled_at?->toIso8601String(),
            'completed_at' => $this->resource->completed_at?->toIso8601String(),
            'course' => $courseRef instanceof CourseRef
                ? [
                    'id' => $courseRef->publicId,
                    'title' => $courseRef->title,
                    'slug' => $courseRef->slug,
                    'thumbnail_path' => $courseRef->thumbnailPath,
                ]
                : $this->whenLoaded('course', fn () => [
                    'id' => $this->resource->course->public_id,
                    'title' => $this->resource->course->title,
                    'slug' => $this->resource->course->slug,
                    'thumbnail_path' => $this->resource->course->thumbnail_path,
                ]),
        ];
    }
}
