<?php

namespace App\Domains\Learning\Database\Factories;

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Identity\Models\User;
use App\Domains\Learning\Models\LessonNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonNote>
 */
class LessonNoteFactory extends Factory
{
    protected $model = LessonNote::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'lesson_id' => Lesson::factory(),
            'body' => fake()->sentence(),
        ];
    }
}
