<?php

namespace App\Domains\Authoring\Http\Controllers\Api\V1\Admin;

use App\Domains\Authoring\Actions\Curriculum\ReorderCurriculumAction;
use App\Domains\Authoring\Http\Requests\ReorderCurriculumRequest;
use App\Domains\Authoring\Http\Resources\CurriculumResource;
use App\Domains\Authoring\Services\CurriculumTreeService;
use App\Domains\Catalog\Models\Course;
use App\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class CurriculumAdminController extends Controller
{
    public function show(Course $course, CurriculumTreeService $tree): JsonResponse
    {
        Gate::authorize('authoring.manage-curriculum', $course);

        return ApiResponse::success(new CurriculumResource($tree->forCourse($course)));
    }

    public function reorder(ReorderCurriculumRequest $request, Course $course, ReorderCurriculumAction $action): JsonResponse
    {
        Gate::authorize('authoring.manage-curriculum', $course);
        $action->execute($course, $request->validated()['tree']);

        return ApiResponse::success(null, 'Curriculum reordered.');
    }
}
