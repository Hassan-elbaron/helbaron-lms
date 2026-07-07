<?php

namespace App\Domains\Learning\Database\Factories;

use App\Domains\Catalog\Models\Course;
use App\Platform\Identity\Models\User;
use App\Domains\Learning\Enums\EnrollmentSource;
use App\Domains\Learning\Enums\EnrollmentStatus;
use App\Domains\Learning\Models\Enrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory()->published(),
            'status' => EnrollmentStatus::Active->value,
            'source' => EnrollmentSource::Free->value,
            'progress_percentage' => 0,
            'enrolled_at' => now(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => EnrollmentStatus::Completed->value,
            'progress_percentage' => 100,
            'completed_at' => now(),
        ]);
    }
}
