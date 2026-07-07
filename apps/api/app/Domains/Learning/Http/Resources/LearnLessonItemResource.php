<?php

namespace App\Domains\Learning\Http\Resources;

use App\Domains\Authoring\Models\Lesson;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * Lesson node inside the learner curriculum tree. Carries progress + lock flags but NO media
 * identifiers — media is fetched separately through the player (LearningMediaService).
 *
 * @property Lesson $resource
 */
class LearnLessonItemResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $completedIds = $this->additional['completed_ids'] ?? [];
        $accessibleIds = $this->additional['accessible_ids'] ?? [];

        return [
            'id' => $this->resource->public_id,
            'title' => $this->resource->title,
            'type' => $this->resource->type->value,
            'is_preview' => $this->resource->is_preview,
            'has_media' => $this->resource->relationLoaded('media') ? $this->resource->media !== null : null,
            'completed' => in_array($this->resource->id, $completedIds, true),
            'locked' => ! in_array($this->resource->id, $accessibleIds, true) && ! $this->resource->is_preview,
        ];
    }
}
