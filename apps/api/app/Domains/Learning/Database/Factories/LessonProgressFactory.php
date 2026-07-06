<?php

namespace App\Domains\Learning\Database\Factories;

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Learning\Enums\LessonProgressStatus;
use App\Domains\Learning\Models\Enrollment;
use App\Domains\Learning\Models\LessonProgress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonProgress>
 */
class LessonProgressFactory extends Factory
{
    protected $model = LessonProgress::class;

    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'lesson_id' => Lesson::factory(),
            'status' => LessonProgressStatus::NotStarted->value,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => LessonProgressStatus::Completed->value,
            'completed_at' => now(),
        ]);
    }
}
