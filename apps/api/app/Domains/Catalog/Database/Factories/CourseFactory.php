<?php

namespace App\Domains\Catalog\Database\Factories;

use App\Domains\Catalog\Enums\CourseStatus;
use App\Domains\Catalog\Models\Course;
use App\Shared\Enums\Visibility;
use App\Shared\Helpers\Slug;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'title' => rtrim($title, '.'),
            'slug' => Slug::make($title).'-'.fake()->unique()->numberBetween(1, 999999),
            'subtitle' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'status' => CourseStatus::Draft->value,
            'visibility' => Visibility::Public->value,
            'is_featured' => false,
            'position' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => CourseStatus::Published->value,
            'published_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => CourseStatus::Archived->value]);
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['visibility' => Visibility::Private->value]);
    }
}
