<?php

namespace App\Domains\Assessment\Database\Factories;

use App\Domains\Assessment\Models\AssessmentTag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AssessmentTag> */
class AssessmentTagFactory extends Factory
{
    protected $model = AssessmentTag::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'kind' => AssessmentTag::KIND_TAG,
            'name' => $name,
            'slug' => Str::slug((string) $name),
        ];
    }

    public function objective(): static
    {
        return $this->state(fn () => ['kind' => AssessmentTag::KIND_OBJECTIVE]);
    }
}
