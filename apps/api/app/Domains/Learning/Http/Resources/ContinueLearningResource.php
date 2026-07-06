<?php

namespace App\Domains\Learning\Http\Resources;

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Learning\Models\Enrollment;
use App\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @property array{enrollment: Enrollment, next_lesson: ?Lesson} $resource
 */
class ContinueLearningResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $enrollment = $this->resource['enrollment'];
        $next = $this->resource['next_lesson'];

        return [
            'course' => [
                'id' => $enrollment->course->public_id,
                'title' => $enrollment->course->title,
            ],
            'progress_percentage' => $enrollment->progress_percentage,
            'next_lesson' => $next ? [
                'id' => $next->public_id,
                'title' => $next->title,
                'type' => $next->type->value,
            ] : null,
        ];
    }
}
