<?php

namespace App\Domains\Assessment\Http\Resources;

use App\Domains\Assessment\Enums\FeedbackMode;
use App\Domains\Assessment\Models\AssessmentAnswer;
use App\Domains\Assessment\Models\AssessmentAttempt;
use App\Domains\Assessment\Models\AssessmentQuestion;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * The learner's view of their own attempt: the paper as served, their saved answers, and — only
 * when entitled — the result.
 *
 * Entitlement to see the answer key is computed HERE, once, from the attempt status and the
 * assessment's feedback mode. Leaving that decision to each controller is how answer keys leak.
 */
class AttemptResource extends JsonResource
{
    /** @param  EloquentCollection<int, AssessmentQuestion>  $questions  only those this sitting served */
    public function __construct(
        AssessmentAttempt $resource,
        private readonly EloquentCollection $questions,
    ) {
        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var AssessmentAttempt $attempt */
        $attempt = $this->resource;
        $assessment = $attempt->assessment;
        $reveal = $this->mayRevealKey($attempt);

        $answers = $attempt->answers->keyBy('question_id');

        return [
            'id' => $attempt->public_id,
            'status' => $attempt->status->value,
            'attempt_number' => $attempt->attempt_number,
            // Not nullsafe: started_at is NOT NULL and is always set when the attempt is created.
            'started_at' => $attempt->started_at->toIso8601String(),
            'expires_at' => $attempt->expires_at?->toIso8601String(),
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),

            // Only present once the attempt is final — an in-progress attempt has no score, and
            // exposing a running total would let a learner probe the key by editing answers.
            'result' => $attempt->status->isFinal() || $attempt->graded_at !== null ? [
                'score' => $attempt->score === null ? null : (float) $attempt->score,
                'max_score' => $attempt->max_score === null ? null : (float) $attempt->max_score,
                'percentage' => $attempt->percentage === null ? null : (float) $attempt->percentage,
                'passed' => $attempt->passed,
            ] : null,

            // Served in the frozen order, so the client renders exactly the paper that was set.
            'questions' => $this->orderedQuestions($attempt)
                ->map(function (AssessmentQuestion $question) use ($answers, $reveal) {
                    $answer = $answers->get($question->id);

                    return [
                        'question' => (new LearnerQuestionResource($question, $reveal))->toArray(request()),
                        'answer' => $answer === null ? null : $this->answer($answer, $reveal),
                    ];
                })->values()->all(),

            'feedback_mode' => $assessment?->feedback_mode->value,
        ];
    }

    /** @return array<string, mixed> */
    private function answer(AssessmentAnswer $answer, bool $reveal): array
    {
        return [
            'response' => $answer->response,
            // Correctness is a form of the answer key, so it obeys the same gate.
            'is_correct' => $reveal ? $answer->is_correct : null,
            'points_awarded' => $reveal && $answer->points_awarded !== null
                ? (float) $answer->points_awarded
                : null,
            'feedback' => $reveal ? $answer->feedback : null,
        ];
    }

    /**
     * The served questions, in the attempt's frozen order. A public_id with no matching row is
     * dropped rather than rendered as a hole — a question deleted mid-attempt should disappear,
     * not break the paper.
     *
     * @return Collection<int, AssessmentQuestion>
     */
    private function orderedQuestions(AssessmentAttempt $attempt): Collection
    {
        /** @var EloquentCollection<string, AssessmentQuestion> $byPublicId */
        $byPublicId = $this->questions->keyBy('public_id');

        return collect($attempt->questionOrder())
            ->map(fn (string $publicId): ?AssessmentQuestion => $byPublicId->get($publicId))
            ->filter()
            ->values();
    }

    /**
     * Immediate feedback reveals as you go; after_submit waits for a final status; never means
     * never. A learner always learns their score — only the KEY is withheld.
     */
    private function mayRevealKey(AssessmentAttempt $attempt): bool
    {
        $mode = $attempt->assessment?->feedback_mode;

        return match ($mode) {
            FeedbackMode::Immediate => true,
            FeedbackMode::AfterSubmit => $attempt->status->isFinal() || $attempt->graded_at !== null,
            default => false,
        };
    }
}
