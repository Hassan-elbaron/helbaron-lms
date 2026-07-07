<?php

namespace App\Contexts\Learning\Http\Controllers\Api\V1;

use App\Domains\Authoring\Models\Lesson;
use App\Contexts\Learning\Actions\Progress\RecordLessonProgressAction;
use App\Contexts\Learning\Enums\LessonProgressStatus;
use App\Contexts\Learning\Http\Requests\RecordProgressRequest;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class LessonProgressController extends Controller
{
    public function store(RecordProgressRequest $request, Lesson $lesson, RecordLessonProgressAction $action): JsonResponse
    {
        $data = $request->validated();
        $progress = $action->execute(
            $request->user(),
            $lesson,
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
