<?php

namespace App\Contexts\Learning\Http\Resources;

use App\Platform\Shared\Curriculum\Contracts\CurriculumReadPort;
use App\Platform\Shared\Curriculum\Data\SectionRef;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * Section node inside the learner curriculum tree. Accepts either a curriculum-tree node
 * (`['section' => SectionRef, 'lessons' => LessonRef[]]`, Phase 3B DTO input) or an Authoring
 * Section model (mapped via CurriculumReadPort — the current controller path). Output is identical.
 */
class LearnSectionResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $completedIds = $this->additional['completed_ids'] ?? [];
        $accessibleIds = $this->additional['accessible_ids'] ?? [];

        if (is_array($this->resource) && ($this->resource['section'] ?? null) instanceof SectionRef) {
            $section = $this->resource['section'];
            $lessons = $this->resource['lessons'];
        } else {
            $section = app(CurriculumReadPort::class)->sectionRef($this->resource);
            $lessons = $this->resource->lessons;
        }

        return [
            'id' => $section->publicId,
            'title' => $section->title,
            'lessons' => collect($lessons)->map(fn ($lesson) => LearnLessonItemResource::make($lesson)
                ->additional(['completed_ids' => $completedIds, 'accessible_ids' => $accessibleIds])
                ->resolve()),
        ];
    }
}
