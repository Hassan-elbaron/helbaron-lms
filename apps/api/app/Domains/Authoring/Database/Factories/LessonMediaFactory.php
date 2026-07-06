<?php

namespace App\Domains\Authoring\Database\Factories;

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\LessonMedia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonMedia>
 */
class LessonMediaFactory extends Factory
{
    protected $model = LessonMedia::class;

    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'mux_asset_id' => 'asset_'.fake()->uuid(),
            'mux_playback_id' => 'pb_'.fake()->uuid(),
            's3_key' => 'media/'.fake()->uuid().'.mp4',
            'mime_type' => 'video/mp4',
            'duration' => fake()->numberBetween(60, 3600),
            'filesize' => fake()->numberBetween(1_000_000, 500_000_000),
        ];
    }
}
