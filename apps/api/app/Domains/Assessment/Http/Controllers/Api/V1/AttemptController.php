<?php

namespace App\Domains\Assessment\Http\Controllers\Api\V1;

use App\Domains\Assessment\Actions\Attempt\SaveAnswerAction;
use App\Domains\Assessment\Actions\Attempt\StartAttemptAction;
use App\Domains\Assessment\Actions\Attempt\SubmitAttemptAction;
use App\Domains\Assessment\Http\Requests\SaveAnswerRequest;
use App\Domains\Assessment\Http\Resources\AttemptResource;
use App\Domains\Assessment\Models\Assessment;
use App\Domains\Assessment\Models\AssessmentAttempt;
use App\Domains\Assessment\Models\AssessmentQuestion;
use App\Platform\Identity\Contracts\Actor;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Learner-facing attempt surface.
 *
 * Authorization here is ownership of the ATTEMPT, not a policy: an attempt belongs to exactly one
 * learner and nobody else may read or write it — not even the instructor, who has separate
 * results endpoints. Every method funnels through assertOwned() for that reason.
 */
class AttemptController
{
    public function start(Request $request, Assessment $assessment, StartAttemptAction $action): JsonResponse
    {
        $attempt = $action->execute($assessment, $this->actorId($request));

        return ApiResponse::created($this->present($attempt));
    }

    public function show(Request $request, AssessmentAttempt $attempt): JsonResponse
    {
        $this->assertOwned($request, $attempt);

        return ApiResponse::success($this->present($attempt));
    }

    public function answer(SaveAnswerRequest $request, AssessmentAttempt $attempt, SaveAnswerAction $action): JsonResponse
    {
        $this->assertOwned($request, $attempt);

        $data = $request->validated();
        $response = $data['response'] ?? null;

        $action->execute($attempt, (string) $data['question_id'], is_array($response) ? $response : null);

        // The saved answer is deliberately NOT echoed with a grade — grading happens at submission.
        return ApiResponse::success(null, 'Answer saved.');
    }

    public function submit(Request $request, AssessmentAttempt $attempt, SubmitAttemptAction $action): JsonResponse
    {
        $this->assertOwned($request, $attempt);

        return ApiResponse::updated($this->present($action->execute($attempt)));
    }

    private function present(AssessmentAttempt $attempt): AttemptResource
    {
        $attempt->load(['assessment', 'answers']);

        // Only the questions this sitting actually served, so a shuffled or subset paper never
        // leaks the questions the learner was not given.
        $questions = AssessmentQuestion::query()
            ->with('options')
            ->where('assessment_id', $attempt->assessment_id)
            ->whereIn('public_id', $attempt->questionOrder())
            ->get();

        return new AttemptResource($attempt, $questions);
    }

    private function assertOwned(Request $request, AssessmentAttempt $attempt): void
    {
        if ((int) $attempt->user_id !== $this->actorId($request)) {
            throw new AccessDeniedHttpException('This attempt does not belong to you.');
        }
    }

    private function actorId(Request $request): int
    {
        $actor = $request->user();

        if (! $actor instanceof Actor) {
            throw new AccessDeniedHttpException('Authentication required.');
        }

        return $actor->actorId();
    }
}
