<?php

namespace App\Domains\Learning\Http\Resources;

use App\Domains\Learning\Models\Enrollment;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @property Enrollment $resource
 */
class MyLearningItemResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'enrollment_id' => $this->resource->public_id,
            'status' => $this->resource->status->value,
            'progress_percentage' => $this->resource->progress_percentage,
            'enrolled_at' => $this->resource->enrolled_at?->toIso8601String(),
            'completed_at' => $this->resource->completed_at?->toIso8601String(),
            'course' => $this->whenLoaded('course', fn () => [
                'id' => $this->resource->course->public_id,
                'title' => $this->resource->course->title,
                'slug' => $this->resource->course->slug,
                'thumbnail_path' => $this->resource->course->thumbnail_path,
            ]),
        ];
    }
}
