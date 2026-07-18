<?php

namespace App\Domains\Assessment\Http\Controllers\Api\V1\Admin;

use App\Domains\Assessment\Actions\Question\DeleteQuestionAction;
use App\Domains\Assessment\Actions\Question\ReorderQuestionsAction;
use App\Domains\Assessment\Actions\Question\SaveQuestionAction;
use App\Domains\Assessment\Http\Requests\ReorderQuestionsRequest;
use App\Domains\Assessment\Http\Requests\SaveQuestionRequest;
use App\Domains\Assessment\Http\Resources\QuestionResource;
use App\Domains\Assessment\Models\Assessment;
use App\Domains\Assessment\Models\AssessmentQuestion;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Questions are authorized through their PARENT assessment, never independently. Resolving the
 * parent from the question's own ancestry is what stops a caller pairing a question id they do not
 * own with an assessment id they do.
 */
class QuestionAdminController
{
    public function store(SaveQuestionRequest $request, Assessment $assessment, SaveQuestionAction $action): JsonResponse
    {
        Gate::authorize('update', $assessment);

        return ApiResponse::created(new QuestionResource($action->addTo($assessment, $request->validated())));
    }

    public function update(SaveQuestionRequest $request, AssessmentQuestion $question, SaveQuestionAction $action): JsonResponse
    {
        $this->authorizeParent($question);

        return ApiResponse::updated(new QuestionResource($action->applyTo($question, $request->validated())));
    }

    public function destroy(AssessmentQuestion $question, DeleteQuestionAction $action): JsonResponse
    {
        $this->authorizeParent($question);

        $action->execute($question);

        return ApiResponse::deleted('Question deleted.');
    }

    public function reorder(ReorderQuestionsRequest $request, Assessment $assessment, ReorderQuestionsAction $action): JsonResponse
    {
        Gate::authorize('update', $assessment);

        $action->execute($assessment, array_values($request->validated()['order']));

        return ApiResponse::success(null, 'Questions reordered.');
    }

    private function authorizeParent(AssessmentQuestion $question): void
    {
        $assessment = $question->assessment;

        // A question whose assessment is gone is not editable by anyone.
        if ($assessment === null) {
            abort(404);
        }

        Gate::authorize('update', $assessment);
    }
}
