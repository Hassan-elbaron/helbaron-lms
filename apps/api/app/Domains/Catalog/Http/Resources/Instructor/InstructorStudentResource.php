<?php

namespace App\Domains\Catalog\Http\Resources\Instructor;

use App\Contexts\Learning\Models\Enrollment;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * An enrolled learner row for the instructor's course roster. The display name / public id are
 * resolved through the Identity UserLookupPort and stashed as attributes by the controller
 * (`student_name`, `student_public_id`) — no Identity model is exposed here. Email is intentionally
 * not surfaced: the boundary-safe UserRef excludes it by design.
 *
 * @property Enrollment $resource
 */
class InstructorStudentResource extends BaseResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'enrollment_id' => $this->resource->public_id,
            'student' => [
                'id' => $this->resource->getAttribute('student_public_id'),
                'name' => $this->resource->getAttribute('student_name'),
            ],
            'status' => $this->resource->status->value,
            'progress_percentage' => (int) $this->resource->progress_percentage,
            'enrolled_at' => $this->resource->enrolled_at?->toIso8601String(),
            'completed_at' => $this->resource->completed_at?->toIso8601String(),
        ];
    }
}
