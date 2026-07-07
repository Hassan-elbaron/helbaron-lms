<?php

namespace App\Domains\Learning\Http\Controllers\Api\V1;

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Learning\Actions\Engagement\UpsertLessonNoteAction;
use App\Domains\Learning\Http\Requests\UpsertNoteRequest;
use App\Domains\Learning\Services\LessonAccessService;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class NoteController extends Controller
{
    public function store(UpsertNoteRequest $request, Lesson $lesson, LessonAccessService $access, UpsertLessonNoteAction $action): JsonResponse
    {
        $access->assertAccess($request->user(), $lesson);
        $note = $action->execute($request->user(), $lesson, $request->validated()['body']);

        return ApiResponse::success(['id' => $note->public_id, 'body' => $note->body], 'Note saved.');
    }
}
