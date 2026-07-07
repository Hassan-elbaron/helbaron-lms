<?php

namespace App\Contexts\Learning\Http\Controllers\Api\V1;

use App\Domains\Authoring\Models\Lesson;
use App\Contexts\Learning\Actions\Engagement\ToggleBookmarkAction;
use App\Contexts\Learning\Services\LessonAccessService;
use App\Platform\Shared\Support\ApiResponse;
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
