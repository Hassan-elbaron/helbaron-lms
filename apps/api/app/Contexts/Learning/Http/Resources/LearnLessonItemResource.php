<?php

namespace App\Contexts\Learning\Http\Resources;

use App\Platform\Shared\Curriculum\Contracts\CurriculumReadPort;
use App\Platform\Shared\Curriculum\Data\LessonRef;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * Lesson node inside the learner curriculum tree. Renders from a LessonRef DTO. Accepts either a
 * LessonRef directly (Phase 3B, DTO input) or an Authoring Lesson model (mapped via
 * CurriculumReadPort — the current controller path). Output is identical.
 */
class LearnLessonItemResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $completedIds = $this->additional['completed_ids'] ?? [];
        $accessibleIds = $this->additional['accessible_ids'] ?? [];

        $lesson = $this->resource instanceof LessonRef
            ? $this->resource
            : app(CurriculumReadPort::class)->lessonRef($this->resource);

        return [
            'id' => $lesson->publicId,
            'title' => $lesson->title,
            'type' => $lesson->type,
            'is_preview' => $lesson->isPreview,
            'has_media' => $lesson->hasMedia,
            'completed' => in_array($lesson->id, $completedIds, true),
            'locked' => ! in_array($lesson->id, $accessibleIds, true) && ! $lesson->isPreview,
        ];
    }
}
