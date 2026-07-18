<?php

namespace App\Domains\Assessment\Database\Factories;

use App\Domains\Assessment\Enums\Difficulty;
use App\Domains\Assessment\Enums\QuestionType;
use App\Domains\Assessment\Models\Assessment;
use App\Domains\Assessment\Models\AssessmentQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AssessmentQuestion> */
class AssessmentQuestionFactory extends Factory
{
    protected $model = AssessmentQuestion::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'type' => QuestionType::SingleChoice->value,
            'prompt' => '<p>'.fake()->sentence(8).'</p>',
            'config' => null,
            'explanation' => null,
            'hint' => null,
            'points' => 1,
            'negative_points' => 0,
            'difficulty' => Difficulty::Medium->value,
            'position' => 0,
        ];
    }

    public function ofType(QuestionType $type): static
    {
        return $this->state(fn () => ['type' => $type->value]);
    }

    public function worth(float $points): static
    {
        return $this->state(fn () => ['points' => $points]);
    }

    public function penalising(float $penalty): static
    {
        return $this->state(fn () => ['negative_points' => $penalty]);
    }

    /** @param  array<string, mixed>  $config */
    public function configured(array $config): static
    {
        return $this->state(fn () => ['config' => $config]);
    }
}
