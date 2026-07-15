<?php

namespace App\Contexts\Learning\Http\Controllers\Api\V1;

use App\Contexts\Learning\Actions\Engagement\ToggleBookmarkAction;
use App\Contexts\Learning\Services\LessonAccessService;
use App\Platform\Shared\Curriculum\Contracts\CurriculumReadPort;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BookmarkController extends Controller
{
    public function store(Request $request, string $lesson, LessonAccessService $access, ToggleBookmarkAction $action, CurriculumReadPort $curriculum): JsonResponse
    {
        $ref = $curriculum->findLessonByPublicId($lesson);
        if ($ref === null) {
            throw new NotFoundHttpException('Lesson not found.');
        }

        $access->assertAccessByUserId($request->user()->id, $ref->id);

        return ApiResponse::success($action->executeByUserId($request->user()->id, $ref->id), 'Bookmark toggled.');
    }
}
