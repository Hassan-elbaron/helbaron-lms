<?php

namespace App\Contexts\Learning\Http\Controllers\Api\V1;

use App\Domains\Authoring\Models\Lesson;
use App\Contexts\Learning\Actions\Engagement\UpsertLessonNoteAction;
use App\Contexts\Learning\Http\Requests\UpsertNoteRequest;
use App\Contexts\Learning\Services\LessonAccessService;
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
