<?php

namespace App\Domains\Catalog\Http\Controllers\Api\V1\Instructor;

use App\Contexts\Learning\Models\Enrollment;
use App\Domains\Catalog\Http\Resources\Instructor\InstructorStudentResource;
use App\Domains\Catalog\Models\Course;
use App\Platform\Identity\Contracts\UserLookupPort;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends InstructorController
{
    /** GET /teach/courses/{course}/students — paginated roster with progress (404 if not mine). */
    public function index(Request $request, Course $course, UserLookupPort $users): JsonResponse
    {
        $course = $this->ownedCourse($request, $course);

        $perPage = (int) config('catalog.pagination.per_page', 15);
        $paginator = Enrollment::query()
            ->where('course_id', $course->id)
            ->latest('enrolled_at')
            ->paginate($perPage);

        $ids = $paginator->getCollection()->pluck('user_id')->map(static fn ($v): int => (int) $v)->all();
        $refs = $users->refsByIds($ids);

        $paginator->getCollection()->transform(function (Enrollment $enrollment) use ($refs): Enrollment {
            $ref = $refs[(int) $enrollment->user_id] ?? null;
            $enrollment->setAttribute('student_name', $ref?->name);
            $enrollment->setAttribute('student_public_id', $ref?->publicId);

            return $enrollment;
        });

        return ApiResponse::paginated($paginator, InstructorStudentResource::class);
    }
}
