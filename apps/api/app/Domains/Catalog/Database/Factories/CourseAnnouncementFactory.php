<?php

namespace App\Domains\Catalog\Database\Factories;

use App\Domains\Catalog\Models\Course;
use App\Domains\Catalog\Models\CourseAnnouncement;
use App\Platform\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseAnnouncement>
 */
class CourseAnnouncementFactory extends Factory
{
    protected $model = CourseAnnouncement::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'author_id' => User::factory(),
            'title' => rtrim(fake()->sentence(4), '.'),
            'body' => fake()->paragraph(),
            'published_at' => now(),
        ];
    }
}
