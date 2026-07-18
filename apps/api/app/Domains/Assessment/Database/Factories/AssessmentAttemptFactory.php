<?php

namespace App\Domains\Assessment\Database\Factories;

use App\Domains\Assessment\Enums\AttemptStatus;
use App\Domains\Assessment\Models\Assessment;
use App\Domains\Assessment\Models\AssessmentAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessmentAttempt>
 *
 * `user_id` is intentionally absent: this context may not import Identity's User model, so the
 * caller must supply it — AssessmentAttempt::factory()->create(['user_id' => $user->id]).
 * Creating without it fails loudly at the database, which is the correct outcome: an attempt with
 * no learner is meaningless.
 */
class AssessmentAttemptFactory extends Factory
{
    protected $model = AssessmentAttempt::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'lesson_id' => null,
            'attempt_number' => 1,
            'assessment_version' => 1,
            'status' => AttemptStatus::InProgress->value,
            'started_at' => now(),
            'expires_at' => null,
            'question_order' => [],
        ];
    }

    /** @param  list<string>  $questionPublicIds */
    public function serving(array $questionPublicIds): static
    {
        return $this->state(fn () => ['question_order' => $questionPublicIds]);
    }

    /** An attempt whose time limit has already elapsed. */
    public function expired(): static
    {
        return $this->state(fn () => [
            'started_at' => now()->subHour(),
            'expires_at' => now()->subMinutes(30),
        ]);
    }

    public function graded(): static
    {
        return $this->state(fn () => [
            'status' => AttemptStatus::Graded->value,
            'submitted_at' => now(),
            'graded_at' => now(),
        ]);
    }
}
