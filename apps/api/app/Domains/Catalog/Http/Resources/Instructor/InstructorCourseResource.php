<?php

namespace App\Domains\Catalog\Http\Resources\Instructor;

use App\Domains\Catalog\Models\Course;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * A course as seen by its trainer, with teaching stats. Expects a `stats_payload` array attribute
 * set by the controller (via InstructorAnalyticsService).
 *
 * @property Course $resource
 */
class InstructorCourseResource extends BaseResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, int>|null $stats */
        $stats = $this->resource->getAttribute('stats_payload');

        return [
            'id' => $this->resource->public_id,
            'title' => $this->resource->title,
            'slug' => $this->resource->slug,
            'subtitle' => $this->resource->subtitle,
            'status' => $this->resource->status->value,
            'visibility' => $this->resource->visibility->value,
            'is_featured' => (bool) $this->resource->is_featured,
            'thumbnail_path' => $this->resource->thumbnail_path,
            'published_at' => $this->resource->published_at?->toIso8601String(),
            'stats' => $stats !== null ? [
                'enrollments' => (int) ($stats['enrollments'] ?? 0),
                'completions' => (int) ($stats['completions'] ?? 0),
                'avg_progress' => (int) ($stats['avg_progress'] ?? 0),
                'sections' => (int) ($stats['sections'] ?? 0),
                'lessons' => (int) ($stats['lessons'] ?? 0),
            ] : null,
        ];
    }
}
