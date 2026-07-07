<?php

namespace App\Domains\Learning\Http\Controllers\Api\V1;

use App\Domains\Catalog\Models\Course;
use App\Domains\Learning\Actions\Enrollment\EnrollInCourseAction;
use App\Domains\Learning\Http\Resources\MyLearningItemResource;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class EnrollmentController extends Controller
{
    public function store(Request $request, Course $course, EnrollInCourseAction $action): JsonResponse
    {
        $enrollment = $action->execute($request->user(), $course);

        return ApiResponse::created(new MyLearningItemResource($enrollment->load('course')), 'Enrolled.');
    }
}
