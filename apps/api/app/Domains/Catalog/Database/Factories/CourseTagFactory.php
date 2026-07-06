<?php

namespace App\Domains\Catalog\Database\Factories;

use App\Domains\Catalog\Models\CourseTag;
use App\Shared\Helpers\Slug;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseTag>
 */
class CourseTagFactory extends Factory
{
    protected $model = CourseTag::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name),
            'slug' => Slug::make($name).'-'.fake()->unique()->numberBetween(1, 99999),
        ];
    }
}
