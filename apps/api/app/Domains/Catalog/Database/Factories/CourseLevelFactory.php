<?php

namespace App\Domains\Catalog\Database\Factories;

use App\Domains\Catalog\Models\CourseLevel;
use App\Platform\Shared\Helpers\Slug;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseLevel>
 */
class CourseLevelFactory extends Factory
{
    protected $model = CourseLevel::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Beginner', 'Intermediate', 'Advanced', 'Expert']);

        return [
            'name' => $name,
            'slug' => Slug::make($name),
            'position' => 0,
        ];
    }
}
