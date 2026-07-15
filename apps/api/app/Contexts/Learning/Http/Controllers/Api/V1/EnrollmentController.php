<?php

namespace App\Contexts\Learning\Http\Controllers\Api\V1;

use App\Contexts\Learning\Actions\Enrollment\EnrollInCourseAction;
use App\Contexts\Learning\Http\Resources\MyLearningItemResource;
use App\Platform\Shared\Curriculum\Contracts\CurriculumReadPort;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EnrollmentController extends Controller
{
    public function store(Request $request, string $course, EnrollInCourseAction $action, CurriculumReadPort $curriculum): JsonResponse
    {
        $courseRef = $curriculum->findCourseByPublicId($course);
        if ($courseRef === null) {
            throw new NotFoundHttpException('Course not found.');
        }

        $enrollment = $action->executeByUserId($request->user()->id, $courseRef->id);

        return ApiResponse::created(new MyLearningItemResource($enrollment->load('course')), 'Enrolled.');
    }
}
