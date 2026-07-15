<?php

namespace App\Contexts\Learning\Http\Resources;

use App\Contexts\Learning\Models\Enrollment;
use App\Platform\Shared\Curriculum\Contracts\CurriculumReadPort;
use App\Platform\Shared\Curriculum\Data\LessonRef;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * @property array{enrollment: Enrollment, next_lesson: Model|LessonRef|null} $resource
 */
class ContinueLearningResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $enrollment = $this->resource['enrollment'];
        $next = $this->resource['next_lesson'];

        $curriculum = app(CurriculumReadPort::class);
        $course = $curriculum->courseRef($enrollment->course);
        $nextLesson = match (true) {
            $next instanceof LessonRef => $next,
            $next !== null => $curriculum->lessonRef($next),
            default => null,
        };

        return [
            'course' => [
                'id' => $course->publicId,
                'title' => $course->title,
            ],
            'progress_percentage' => $enrollment->progress_percentage,
            'next_lesson' => $nextLesson !== null ? [
                'id' => $nextLesson->publicId,
                'title' => $nextLesson->title,
                'type' => $nextLesson->type,
            ] : null,
        ];
    }
}
