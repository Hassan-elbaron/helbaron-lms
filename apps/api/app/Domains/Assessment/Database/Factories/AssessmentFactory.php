<?php

namespace App\Domains\Assessment\Database\Factories;

use App\Domains\Assessment\Enums\AssessmentScope;
use App\Domains\Assessment\Enums\AssessmentStatus;
use App\Domains\Assessment\Enums\FeedbackMode;
use App\Domains\Assessment\Models\Assessment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assessment>
 *
 * `course_id` is left null. This context may not import Catalog's Course model (Deptrac), so tests
 * pass a course id explicitly: Assessment::factory()->create(['course_id' => $course->id]).
 * A null course means a platform-level bank, which is a legitimate state.
 */
class AssessmentFactory extends Factory
{
    protected $model = Assessment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'course_id' => null,
            'title' => rtrim(fake()->sentence(3), '.'),
            'description' => null,
            'scope' => AssessmentScope::Lesson->value,
            'status' => AssessmentStatus::Draft->value,
            'passing_score' => 60,
            'negative_marking' => false,
            'max_attempts' => null,
            'time_limit_seconds' => null,
            'shuffle_questions' => false,
            'shuffle_options' => false,
            'questions_per_attempt' => null,
            'feedback_mode' => FeedbackMode::AfterSubmit->value,
            'version' => 1,
            'created_by' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => AssessmentStatus::Published->value]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => AssessmentStatus::Archived->value]);
    }

    public function timed(int $seconds = 600): static
    {
        return $this->state(fn () => ['time_limit_seconds' => $seconds]);
    }

    public function withNegativeMarking(): static
    {
        return $this->state(fn () => ['negative_marking' => true]);
    }
}
