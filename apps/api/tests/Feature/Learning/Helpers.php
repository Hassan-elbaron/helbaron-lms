<?php

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Domains\Catalog\Models\Course;

/**
 * Build a published, FREE course with N published lessons in one published section.
 *
 * `free()` is explicit because `courses.is_free` defaults to false (fail-closed — a course is not
 * giveable-away until somebody says so). This helper stands for "an ordinary course a learner can
 * enrol in", which is what every caller means by it, so it declares the course free rather than
 * leaving each test to discover the default.
 */
function publishedCourseWithLessons(int $count = 1): array
{
    $course = Course::factory()->published()->free()->create();
    $section = Section::factory()->published()->create(['course_id' => $course->id]);
    $lessons = collect(range(1, $count))->map(fn ($i) => Lesson::factory()->published()->create([
        'section_id' => $section->id, 'position' => $i,
    ]));

    return [$course, $section, $lessons];
}
