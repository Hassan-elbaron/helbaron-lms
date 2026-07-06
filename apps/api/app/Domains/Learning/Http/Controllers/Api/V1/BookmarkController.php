<?php

namespace App\Domains\Learning\Http\Controllers\Api\V1;

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Learning\Actions\Engagement\ToggleBookmarkAction;
use App\Domains\Learning\Services\LessonAccessService;
use App\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BookmarkController extends Controller
{
    public function store(Request $request, Lesson $lesson, LessonAccessService $access, ToggleBookmarkAction $action): JsonResponse
    {
        $access->assertAccess($request->user(), $lesson);

        return ApiResponse::success($action->execute($request->user(), $lesson), 'Bookmark toggled.');
    }
}
