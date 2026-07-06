<?php

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Domains\Catalog\Models\Course;

/** Build a published course with N published lessons in one published section. */
function publishedCourseWithLessons(int $count = 1): array
{
    $course = Course::factory()->published()->create();
    $section = Section::factory()->published()->create(['course_id' => $course->id]);
    $lessons = collect(range(1, $count))->map(fn ($i) => Lesson::factory()->published()->create([
        'section_id' => $section->id, 'position' => $i,
    ]));

    return [$course, $section, $lessons];
}
