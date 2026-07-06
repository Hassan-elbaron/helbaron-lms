<?php

namespace App\Domains\Live\Database\Factories;

use App\Domains\Live\Models\LiveCourse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LiveCourse>
 */
class LiveCourseFactory extends Factory
{
    protected $model = LiveCourse::class;

    public function definition(): array
    {
        return [
            'title' => rtrim(fake()->sentence(3), '.'),
            'description' => fake()->paragraph(),
            'timezone' => 'Asia/Riyadh',
            'is_active' => true,
        ];
    }
}
