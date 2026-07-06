<?php

namespace App\Domains\Authoring\Database\Factories;

use App\Domains\Authoring\Enums\PublishState;
use App\Domains\Authoring\Models\Section;
use App\Domains\Catalog\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    protected $model = Section::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => rtrim(fake()->sentence(3), '.'),
            'summary' => fake()->sentence(),
            'position' => 0,
            'publish_state' => PublishState::Draft->value,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['publish_state' => PublishState::Published->value]);
    }
}
