<?php

namespace App\Contexts\Learning\Http\Controllers\Api\V1;

use App\Contexts\Learning\Exceptions\NotEnrolledException;
use App\Contexts\Learning\Http\Resources\LearnCourseResource;
use App\Contexts\Learning\Models\Enrollment;
use App\Contexts\Learning\Models\LessonProgress;
use App\Contexts\Learning\Services\LessonAccessService;
use App\Platform\Shared\Curriculum\Contracts\CurriculumReadPort;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LearnController extends Controller
{
    public function show(Request $request, string $course, CurriculumReadPort $curriculum, LessonAccessService $access): JsonResponse
    {
        $courseRef = $curriculum->findCourseByPublicId($course);
        if ($courseRef === null) {
            throw new NotFoundHttpException('Course not found.');
        }

        $enrollment = Enrollment::where('user_id', $request->user()->id)
            ->where('course_id', $courseRef->id)
            ->active()
            ->first();

        if ($enrollment === null) {
            throw new NotEnrolledException;
        }

        $tree = $curriculum->curriculumTree($courseRef->id, publishedOnly: true);

        $completedIds = LessonProgress::where('enrollment_id', $enrollment->id)
            ->where('status', 'completed')->pluck('lesson_id')->all();

        // Accessible = preview OR all prerequisites completed.
        $accessibleIds = [];
        foreach ($tree['sections'] as $node) {
            foreach ($node['lessons'] as $lessonRef) {
                if ($access->canAccessByUserId($request->user()->id, $lessonRef->id)) {
                    $accessibleIds[] = $lessonRef->id;
                }
            }
        }

        return ApiResponse::success(new LearnCourseResource([
            'course' => $courseRef,
            'enrollment' => $enrollment,
            'sections' => $tree['sections'],
            'completed_ids' => $completedIds,
            'accessible_ids' => $accessibleIds,
        ]));
    }
}
