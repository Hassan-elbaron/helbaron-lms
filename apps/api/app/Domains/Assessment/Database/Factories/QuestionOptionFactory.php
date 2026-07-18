<?php

namespace App\Domains\Assessment\Database\Factories;

use App\Domains\Assessment\Models\AssessmentQuestion;
use App\Domains\Assessment\Models\QuestionOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<QuestionOption> */
class QuestionOptionFactory extends Factory
{
    protected $model = QuestionOption::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'question_id' => AssessmentQuestion::factory(),
            'label' => rtrim(fake()->sentence(3), '.'),
            'value' => null,
            'is_correct' => false,
            'group_index' => 0,
            'feedback' => null,
            'position' => 0,
        ];
    }

    public function correct(): static
    {
        return $this->state(fn () => ['is_correct' => true]);
    }

    /** An accepted answer for a text-matched question. */
    public function accepting(string $value, int $groupIndex = 0): static
    {
        return $this->state(fn () => [
            'label' => null,
            'value' => $value,
            'is_correct' => true,
            'group_index' => $groupIndex,
        ]);
    }
}
