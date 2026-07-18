<?php

namespace App\Domains\Assessment\Actions\Attempt;

use App\Domains\Assessment\Enums\AttemptStatus;
use App\Domains\Assessment\Models\Assessment;
use App\Domains\Assessment\Models\AssessmentAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Opens an attempt, or returns the learner's existing open one.
 *
 * Resuming rather than creating a second attempt is deliberate: a refreshed tab, a dropped
 * connection or a double-tap must not consume one of the learner's limited attempts.
 */
class StartAttemptAction
{
    public function execute(Assessment $assessment, int $userId, ?int $lessonId = null): AssessmentAttempt
    {
        if (! $assessment->isAttemptable()) {
            throw ValidationException::withMessages([
                'assessment' => 'This assessment is not currently open for attempts.',
            ]);
        }

        return DB::transaction(function () use ($assessment, $userId, $lessonId): AssessmentAttempt {
            // Lock the learner's attempt rows so two concurrent requests cannot both pass the
            // max-attempts check and both create attempt number N.
            $existing = AssessmentAttempt::query()
                ->where('assessment_id', $assessment->id)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->get();

            $open = $existing->firstWhere(fn (AssessmentAttempt $a) => $a->status->isOpen());

            if ($open !== null) {
                // An open attempt whose clock has run out is closed and scored, not resumed.
                if ($open->hasExpired()) {
                    $open->forceFill(['status' => AttemptStatus::Expired->value])->save();
                } else {
                    return $open;
                }
            }

            if ($assessment->max_attempts !== null && $existing->count() >= $assessment->max_attempts) {
                throw ValidationException::withMessages([
                    'assessment' => 'You have used all available attempts for this assessment.',
                ]);
            }

            $questionIds = $this->buildQuestionOrder($assessment);

            if ($questionIds === []) {
                throw ValidationException::withMessages([
                    'assessment' => 'This assessment has no questions.',
                ]);
            }

            $startedAt = now();

            return AssessmentAttempt::create([
                'assessment_id' => $assessment->id,
                'user_id' => $userId,
                'lesson_id' => $lessonId,
                'attempt_number' => ((int) $existing->max('attempt_number')) + 1,
                // Pin the version so later edits never change what this learner was asked.
                'assessment_version' => $assessment->version,
                'status' => AttemptStatus::InProgress->value,
                'started_at' => $startedAt,
                'expires_at' => $assessment->isTimed()
                    ? $startedAt->copy()->addSeconds((int) $assessment->time_limit_seconds)
                    : null,
                'question_order' => $questionIds,
            ]);
        });
    }

    /**
     * Freeze the exact questions this sitting will serve. Shuffling and subsetting happen ONCE,
     * here — never at render time — so the paper is stable across reloads and auditable afterwards.
     *
     * @return list<string>
     */
    private function buildQuestionOrder(Assessment $assessment): array
    {
        $ids = $assessment->questions()->pluck('public_id')->all();

        if ($assessment->shuffle_questions) {
            shuffle($ids);
        }

        $limit = $assessment->questions_per_attempt;

        if ($limit !== null && $limit > 0 && $limit < count($ids)) {
            // When subsetting without shuffling, take the author's first N rather than a random
            // slice — that is the intuitive reading of "serve 10 of these".
            $ids = array_slice($ids, 0, $limit);
        }

        return array_values(array_filter($ids, 'is_string'));
    }
}
