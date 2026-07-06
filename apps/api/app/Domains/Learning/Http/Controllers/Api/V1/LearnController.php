<?php

namespace App\Domains\Learning\Http\Controllers\Api\V1;

use App\Domains\Authoring\Services\CurriculumTreeService;
use App\Domains\Catalog\Models\Course;
use App\Domains\Learning\Exceptions\NotEnrolledException;
use App\Domains\Learning\Http\Resources\LearnCourseResource;
use App\Domains\Learning\Models\Enrollment;
use App\Domains\Learning\Models\LessonProgress;
use App\Domains\Learning\Services\LessonAccessService;
use App\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LearnController extends Controller
{
    public function show(Request $request, string $course, CurriculumTreeService $tree, LessonAccessService $access): JsonResponse
    {
        $courseModel = Course::where('public_id', $course)->first();
        if ($courseModel === null) {
            throw new NotFoundHttpException('Course not found.');
        }

        $enrollment = Enrollment::where('user_id', $request->user()->id)
            ->where('course_id', $courseModel->id)
            ->active()
            ->first();

        if ($enrollment === null) {
            throw new NotEnrolledException;
        }

        $sections = $tree->forCourse($courseModel, publishedOnly: true);

        $completedIds = LessonProgress::where('enrollment_id', $enrollment->id)
            ->where('status', 'completed')->pluck('lesson_id')->all();

        // Accessible = preview OR all prerequisites completed.
        $accessibleIds = [];
        foreach ($sections as $section) {
            foreach ($section->lessons as $lesson) {
                if ($access->canAccess($request->user(), $lesson)) {
                    $accessibleIds[] = $lesson->id;
                }
            }
        }

        return ApiResponse::success(new LearnCourseResource([
            'course' => $courseModel,
            'enrollment' => $enrollment,
            'sections' => $sections,
            'completed_ids' => $completedIds,
            'accessible_ids' => $accessibleIds,
        ]));
    }
}
