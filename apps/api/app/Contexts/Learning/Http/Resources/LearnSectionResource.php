<?php

namespace App\Contexts\Learning\Http\Resources;

use App\Domains\Authoring\Models\Section;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @property Section $resource
 */
class LearnSectionResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $completedIds = $this->additional['completed_ids'] ?? [];
        $accessibleIds = $this->additional['accessible_ids'] ?? [];

        return [
            'id' => $this->resource->public_id,
            'title' => $this->resource->title,
            'lessons' => $this->resource->lessons->map(fn ($lesson) => LearnLessonItemResource::make($lesson)
                ->additional(['completed_ids' => $completedIds, 'accessible_ids' => $accessibleIds])
                ->resolve()),
        ];
    }
}
