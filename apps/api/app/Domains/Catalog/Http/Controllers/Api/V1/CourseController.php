<?php

namespace App\Domains\Catalog\Http\Controllers\Api\V1;

use App\Domains\Catalog\Http\Requests\CourseIndexRequest;
use App\Domains\Catalog\Http\Resources\CourseListResource;
use App\Domains\Catalog\Http\Resources\CourseResource;
use App\Domains\Catalog\Models\Course;
use App\Domains\Catalog\Services\CourseSearchService;
use App\Domains\Catalog\Services\RelatedCoursesService;
use App\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CourseController extends Controller
{
    public function index(CourseIndexRequest $request, CourseSearchService $search): JsonResponse
    {
        $perPage = (int) ($request->validated()['per_page'] ?? config('catalog.pagination.per_page'));
        $paginator = $search->paginate($request->validated(), $perPage);

        return ApiResponse::paginated($paginator, CourseListResource::class);
    }

    public function show(string $publicId, RelatedCoursesService $related): JsonResponse
    {
        if (! Str::isUuid($publicId)) {
            throw new NotFoundHttpException('Course not found.');
        }

        $course = Course::query()
            ->published()
            ->visible()
            ->with(['level', 'language', 'categories', 'tags', 'trainers.profile'])
            ->where('public_id', $publicId)
            ->first();

        if ($course === null) {
            throw new NotFoundHttpException('Course not found.');
        }

        $course->setRelation('related', $related->for($course));

        return ApiResponse::success(new CourseResource($course));
    }
}
