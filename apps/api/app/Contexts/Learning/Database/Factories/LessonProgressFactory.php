<?php

namespace App\Contexts\Learning\Database\Factories;

use App\Contexts\Learning\Enums\LessonProgressStatus;
use App\Contexts\Learning\Models\Enrollment;
use App\Contexts\Learning\Models\LessonProgress;
use App\Domains\Authoring\Models\Lesson;
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
