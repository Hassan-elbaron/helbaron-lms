<?php

namespace App\Domains\Assessment\Database\Factories;

use App\Domains\Assessment\Models\AssessmentAnswer;
use App\Domains\Assessment\Models\AssessmentAttempt;
use App\Domains\Assessment\Models\AssessmentQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AssessmentAnswer> */
class AssessmentAnswerFactory extends Factory
{
    protected $model = AssessmentAnswer::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'attempt_id' => AssessmentAttempt::factory(),
            'question_id' => AssessmentQuestion::factory(),
            'response' => null,
            'is_correct' => null,
            'points_awarded' => null,
        ];
    }

    /** @param  list<string>  $optionPublicIds */
    public function choosing(array $optionPublicIds): static
    {
        return $this->state(fn () => ['response' => ['option_ids' => $optionPublicIds]]);
    }

    public function answering(string $text): static
    {
        return $this->state(fn () => ['response' => ['text' => $text]]);
    }

    /** @param  array<string, string>  $blanks */
    public function filling(array $blanks): static
    {
        return $this->state(fn () => ['response' => ['blanks' => $blanks]]);
    }
}
