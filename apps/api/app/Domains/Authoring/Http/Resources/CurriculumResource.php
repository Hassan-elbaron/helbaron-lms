<?php

namespace App\Domains\Authoring\Http\Resources;

use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * The full curriculum tree for a course (collection of sections with nested lessons).
 * Wrap a section collection: new CurriculumResource($sections).
 *
 * @property Collection $resource
 */
class CurriculumResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'sections' => SectionResource::collection($this->resource),
        ];
    }
}
