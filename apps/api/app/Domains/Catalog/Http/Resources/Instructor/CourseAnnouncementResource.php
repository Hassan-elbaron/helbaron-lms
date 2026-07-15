<?php

namespace App\Domains\Catalog\Http\Resources\Instructor;

use App\Domains\Catalog\Models\CourseAnnouncement;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * A course announcement as shown in the Instructor Portal.
 *
 * @property CourseAnnouncement $resource
 */
class CourseAnnouncementResource extends BaseResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'title' => $this->resource->title,
            'body' => $this->resource->body,
            'published_at' => $this->resource->published_at?->toIso8601String(),
            'created_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
