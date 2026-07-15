<?php

namespace App\Contexts\Learning\Http\Controllers\Api\V1;

use App\Contexts\Learning\Actions\Progress\RecordLessonProgressAction;
use App\Contexts\Learning\Enums\LessonProgressStatus;
use App\Contexts\Learning\Http\Requests\RecordProgressRequest;
use App\Platform\Shared\Curriculum\Contracts\CurriculumReadPort;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LessonProgressController extends Controller
{
    public function store(RecordProgressRequest $request, string $lesson, RecordLessonProgressAction $action, CurriculumReadPort $curriculum): JsonResponse
    {
        $ref = $curriculum->findLessonByPublicId($lesson);
        if ($ref === null) {
            throw new NotFoundHttpException('Lesson not found.');
        }

        $data = $request->validated();
        $progress = $action->executeByUserId(
            $request->user()->id,
            $ref->id,
            LessonProgressStatus::from($data['status']),
            $data['position_seconds'] ?? null,
        );

        return ApiResponse::success([
            'status' => $progress->status->value,
            'position_seconds' => $progress->position_seconds,
            'course_progress_percentage' => $progress->enrollment->progress_percentage,
        ], 'Progress recorded.');
    }
}
