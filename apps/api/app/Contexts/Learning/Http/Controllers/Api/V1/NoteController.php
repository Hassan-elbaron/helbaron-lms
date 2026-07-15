<?php

namespace App\Contexts\Learning\Http\Controllers\Api\V1;

use App\Contexts\Learning\Actions\Engagement\UpsertLessonNoteAction;
use App\Contexts\Learning\Http\Requests\UpsertNoteRequest;
use App\Contexts\Learning\Services\LessonAccessService;
use App\Platform\Shared\Curriculum\Contracts\CurriculumReadPort;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NoteController extends Controller
{
    public function store(UpsertNoteRequest $request, string $lesson, LessonAccessService $access, UpsertLessonNoteAction $action, CurriculumReadPort $curriculum): JsonResponse
    {
        $ref = $curriculum->findLessonByPublicId($lesson);
        if ($ref === null) {
            throw new NotFoundHttpException('Lesson not found.');
        }

        $access->assertAccessByUserId($request->user()->id, $ref->id);
        $note = $action->executeByUserId($request->user()->id, $ref->id, $request->validated()['body']);

        return ApiResponse::success(['id' => $note->public_id, 'body' => $note->body], 'Note saved.');
    }
}
