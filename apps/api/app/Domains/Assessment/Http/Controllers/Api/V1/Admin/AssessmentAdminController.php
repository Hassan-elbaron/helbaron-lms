<?php

namespace App\Domains\Assessment\Http\Controllers\Api\V1\Admin;

use App\Domains\Assessment\Actions\Assessment\CreateAssessmentAction;
use App\Domains\Assessment\Actions\Assessment\DeleteAssessmentAction;
use App\Domains\Assessment\Actions\Assessment\SetAssessmentStatusAction;
use App\Domains\Assessment\Actions\Assessment\UpdateAssessmentAction;
use App\Domains\Assessment\Enums\AssessmentStatus;
use App\Domains\Assessment\Http\Requests\SaveAssessmentRequest;
use App\Domains\Assessment\Http\Requests\SetAssessmentStatusRequest;
use App\Domains\Assessment\Http\Resources\AssessmentResource;
use App\Domains\Assessment\Models\Assessment;
use App\Platform\Identity\Contracts\Actor;
use App\Platform\Identity\Contracts\CourseAccessPort;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Authoring surface for assessments. Course-scoped routes take the course PUBLIC id as a plain
 * string and resolve it through CourseAccessPort — this context may not import the Course model,
 * so there is no route-model binding for it.
 */
class AssessmentAdminController
{
    public function __construct(private readonly CourseAccessPort $courses) {}

    public function index(Request $request, string $course): JsonResponse
    {
        $courseId = $this->manageableCourse($request, $course);

        $assessments = Assessment::query()
            ->withCount('questions')
            ->where('course_id', $courseId)
            ->orderByDesc('id')
            ->get();

        return ApiResponse::success(AssessmentResource::collection($assessments));
    }

    public function store(SaveAssessmentRequest $request, string $course, CreateAssessmentAction $action): JsonResponse
    {
        $courseId = $this->manageableCourse($request, $course);
        $actor = $this->actor($request);

        $assessment = $action->execute($courseId, $actor->actorId(), $request->validated());

        return ApiResponse::created(new AssessmentResource($assessment->loadCount('questions')));
    }

    public function show(Assessment $assessment): JsonResponse
    {
        Gate::authorize('view', $assessment);

        return ApiResponse::success(
            new AssessmentResource($assessment->load('questions.options')->loadCount('questions')),
        );
    }

    public function update(SaveAssessmentRequest $request, Assessment $assessment, UpdateAssessmentAction $action): JsonResponse
    {
        Gate::authorize('update', $assessment);

        return ApiResponse::updated(
            new AssessmentResource($action->execute($assessment, $request->validated())->loadCount('questions')),
        );
    }

    public function destroy(Assessment $assessment, DeleteAssessmentAction $action): JsonResponse
    {
        Gate::authorize('delete', $assessment);

        $action->execute($assessment);

        return ApiResponse::deleted('Assessment deleted.');
    }

    public function status(SetAssessmentStatusRequest $request, Assessment $assessment, SetAssessmentStatusAction $action): JsonResponse
    {
        Gate::authorize('update', $assessment);

        $status = AssessmentStatus::from((string) $request->validated()['status']);

        return ApiResponse::updated(
            new AssessmentResource($action->execute($assessment, $status)->loadCount('questions')),
        );
    }

    /** Resolves the course id, or 404s — never revealing whether the course exists. */
    private function manageableCourse(Request $request, string $coursePublicId): int
    {
        $courseId = $this->courses->manageableCourseId($this->actor($request), $coursePublicId);

        if ($courseId === null) {
            throw new NotFoundHttpException('Course not found.');
        }

        return $courseId;
    }

    private function actor(Request $request): Actor
    {
        $actor = $request->user();

        if (! $actor instanceof Actor) {
            throw new NotFoundHttpException('Course not found.');
        }

        return $actor;
    }
}
