<?php

use App\Domains\Authoring\Enums\PublishState;
use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Domains\Authoring\Services\CurriculumValidator;
use App\Domains\Catalog\Database\Seeders\CatalogSeeder;
use App\Domains\Catalog\Enums\CourseStatus;
use App\Domains\Catalog\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('never seeds a Published course without at least one section and one published lesson', function () {
    $this->seed(CatalogSeeder::class);

    $published = Course::where('status', CourseStatus::Published->value)->get();

    expect($published)->not->toBeEmpty();

    /** @var CurriculumValidator $validator */
    $validator = app(CurriculumValidator::class);

    foreach ($published as $course) {
        $sectionIds = Section::where('course_id', $course->id)->pluck('id');

        expect($sectionIds)->not->toBeEmpty(
            "Published course '{$course->title}' has no sections."
        );

        $publishedLessons = Lesson::whereIn('section_id', $sectionIds)
            ->where('publish_state', PublishState::Published->value)
            ->count();

        expect($publishedLessons)->toBeGreaterThan(
            0,
            "Published course '{$course->title}' has no published lessons."
        );

        // The app's own publish guard must accept every seeded Published course.
        expect($validator->validateForPublish($course))->toBe(
            [],
            "Published course '{$course->title}' fails the publish invariant."
        );
    }
});

it('leaves any course that lacks a publishable curriculum as Draft, never Published', function () {
    $this->seed(CatalogSeeder::class);

    /** @var CurriculumValidator $validator */
    $validator = app(CurriculumValidator::class);

    // Every course that is content-less (no sections, or no published lesson) must be Draft.
    foreach (Course::all() as $course) {
        $sectionIds = Section::where('course_id', $course->id)->pluck('id');
        $hasPublishedLesson = Lesson::whereIn('section_id', $sectionIds)
            ->where('publish_state', PublishState::Published->value)
            ->exists();

        if ($sectionIds->isEmpty() || ! $hasPublishedLesson) {
            expect($course->status->value)->toBe(
                CourseStatus::Draft->value,
                "Content-less course '{$course->title}' must be Draft, not {$course->status->value}."
            );
        }
    }
});

it('is idempotent and deterministic: re-seeding does not duplicate courses, sections or lessons', function () {
    $this->seed(CatalogSeeder::class);

    $courses1 = Course::count();
    $sections1 = Section::count();
    $lessons1 = Lesson::count();

    // Re-run the same seeder.
    $this->seed(CatalogSeeder::class);

    expect(Course::count())->toBe($courses1)
        ->and(Section::count())->toBe($sections1)
        ->and(Lesson::count())->toBe($lessons1);

    // Deterministic shape: exactly one "Getting Started" section per seeded course, each with
    // its two published lessons.
    $published = Course::where('status', CourseStatus::Published->value)->get();
    foreach ($published as $course) {
        $sections = Section::where('course_id', $course->id)->get();
        expect($sections)->toHaveCount(1);
        $lessonCount = Lesson::where('section_id', $sections->first()->id)->count();
        expect($lessonCount)->toBe(2);
    }
});
