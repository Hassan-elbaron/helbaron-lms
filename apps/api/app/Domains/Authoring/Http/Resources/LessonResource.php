<?php

namespace App\Domains\Authoring\Http\Resources;

use App\Domains\Authoring\Models\Lesson;
use App\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @property Lesson $resource
 */
class LessonResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'title' => $this->resource->title,
            'type' => $this->resource->type->value,
            'content' => $this->resource->content,
            'position' => $this->resource->position,
            'publish_state' => $this->resource->publish_state->value,
            'is_preview' => $this->resource->is_preview,
            'media' => new LessonMediaResource($this->whenLoaded('media')),
            'prerequisites' => $this->whenLoaded('prerequisites', fn () => $this->resource->prerequisites->map(fn ($p) => [
                'id' => $p->public_id, 'title' => $p->title,
            ])->values()),
        ];
    }
}
