<?php

use App\Domains\Authoring\Enums\PublishState;
use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Domains\Catalog\Actions\Course\PublishCourseAction;
use App\Domains\Catalog\Exceptions\CoursePublishBlockedException;
use App\Domains\Catalog\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('blocks publishing a course with no curriculum (guard bound by Authoring)', function () {
    $course = Course::factory()->create();

    expect(fn () => app(PublishCourseAction::class)->execute($course))
        ->toThrow(CoursePublishBlockedException::class);
});

it('allows publishing once a published lesson exists', function () {
    $course = Course::factory()->create();
    $section = Section::factory()->create(['course_id' => $course->id, 'publish_state' => PublishState::Published->value]);
    Lesson::factory()->published()->create(['section_id' => $section->id]);

    $published = app(PublishCourseAction::class)->execute($course);

    expect($published->isPublished())->toBeTrue();
});
