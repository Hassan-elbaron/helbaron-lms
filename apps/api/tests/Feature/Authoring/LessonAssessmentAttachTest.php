<?php

use App\Domains\Assessment\Models\Assessment;
use App\Domains\Authoring\Enums\LessonType;
use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Domains\Catalog\Enums\CourseStatus;
use App\Domains\Catalog\Models\Course;
use App\Platform\Identity\Database\Seeders\IdentitySeeder;
use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as SpatieRole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(IdentitySeeder::class);
});

function attachInstructor(): User
{
    $user = User::factory()->create();
    $user->assignRole(SpatieRole::findByName('instructor', 'web'));

    return $user;
}

function quizLessonOn(Course $course): Lesson
{
    $section = Section::factory()->create(['course_id' => $course->id]);

    return Lesson::factory()->create([
        'section_id' => $section->id,
        'type' => LessonType::Quiz->value,
    ]);
}

it('attaches an assessment from the same course and exposes it on the lesson', function () {
    $instructor = attachInstructor();
    $course = Course::factory()->create(['status' => CourseStatus::Draft]);
    $course->syncTrainers([$instructor->id]);

    $lesson = quizLessonOn($course);
    $assessment = Assessment::factory()->create(['course_id' => $course->id, 'title' => 'Module quiz']);

    $this->actingAs($instructor, 'sanctum')
        ->putJson("/api/v1/admin/lessons/{$lesson->public_id}/assessment", [
            'assessment_id' => $assessment->public_id,
        ])
        ->assertOk()
        ->assertJsonPath('data.assessment.id', $assessment->public_id)
        ->assertJsonPath('data.assessment.title', 'Module quiz');

    expect($lesson->refresh()->assessment_id)->toBe($assessment->id);
});

it('refuses an assessment belonging to a different course', function () {
    $instructor = attachInstructor();
    $own = Course::factory()->create(['status' => CourseStatus::Draft]);
    $own->syncTrainers([$instructor->id]);

    $lesson = quizLessonOn($own);

    // The instructor also trains the other course, so this is purely about cross-course leakage —
    // an assessment must never be attachable outside the course that owns it, even by someone
    // authorized on both.
    $other = Course::factory()->create(['status' => CourseStatus::Draft]);
    $other->syncTrainers([$instructor->id]);
    $foreign = Assessment::factory()->create(['course_id' => $other->id]);

    $this->actingAs($instructor, 'sanctum')
        ->putJson("/api/v1/admin/lessons/{$lesson->public_id}/assessment", [
            'assessment_id' => $foreign->public_id,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('assessment_id');

    expect($lesson->refresh()->assessment_id)->toBeNull();
});

it('detaches with an explicit null', function () {
    $instructor = attachInstructor();
    $course = Course::factory()->create(['status' => CourseStatus::Draft]);
    $course->syncTrainers([$instructor->id]);

    $assessment = Assessment::factory()->create(['course_id' => $course->id]);
    $lesson = quizLessonOn($course);
    $lesson->forceFill(['assessment_id' => $assessment->id])->save();

    $this->actingAs($instructor, 'sanctum')
        ->putJson("/api/v1/admin/lessons/{$lesson->public_id}/assessment", ['assessment_id' => null])
        ->assertOk()
        ->assertJsonPath('data.assessment', null);

    expect($lesson->refresh()->assessment_id)->toBeNull();
});

it('denies an instructor who does not train the course', function () {
    $owner = attachInstructor();
    $outsider = attachInstructor();

    $course = Course::factory()->create(['status' => CourseStatus::Draft]);
    $course->syncTrainers([$owner->id]);

    $lesson = quizLessonOn($course);
    $assessment = Assessment::factory()->create(['course_id' => $course->id]);

    $this->actingAs($outsider, 'sanctum')
        ->putJson("/api/v1/admin/lessons/{$lesson->public_id}/assessment", [
            'assessment_id' => $assessment->public_id,
        ])
        ->assertForbidden();
});
