<?php

use App\Domains\Assessment\Enums\AttemptStatus;
use App\Domains\Assessment\Models\Assessment;
use App\Domains\Assessment\Models\AssessmentAttempt;
use App\Domains\Authoring\Enums\LessonType;
use App\Domains\Authoring\Enums\PublishState;
use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Domains\Catalog\Models\Course;
use App\Platform\Identity\Database\Seeders\IdentitySeeder;
use App\Platform\Identity\Models\User;
use App\Platform\Shared\Assessment\Contracts\AssessmentStatsPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as SpatieRole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(IdentitySeeder::class);
});

/** @return array{0: Course, 1: User, 2: Lesson} */
function statsFixture(): array
{
    $course = Course::factory()->create();
    $instructor = User::factory()->create();
    $instructor->assignRole(SpatieRole::findByName('instructor', 'web'));
    $course->syncTrainers([$instructor->id]);

    $section = Section::factory()->create([
        'course_id' => $course->id,
        'publish_state' => PublishState::Published->value,
    ]);
    $assessment = Assessment::factory()->create(['course_id' => $course->id, 'status' => 'published']);
    $lesson = Lesson::factory()->create([
        'section_id' => $section->id,
        'type' => LessonType::Quiz->value,
        'publish_state' => PublishState::Published->value,
        'assessment_id' => $assessment->id,
    ]);

    return [$course, $instructor, $lesson];
}

function attempt(Lesson $lesson, ?bool $passed, string $status = 'graded'): AssessmentAttempt
{
    return AssessmentAttempt::factory()->create([
        'assessment_id' => $lesson->assessment_id,
        'user_id' => User::factory()->create()->id,
        'lesson_id' => $lesson->id,
        'status' => $status,
        'passed' => $passed,
    ]);
}

it('returns no pass rate when nothing has been graded', function () {
    [, , $lesson] = statsFixture();

    $stats = app(AssessmentStatsPort::class)->passRateForLessons([$lesson->id]);

    // Null, NOT zero. Nobody failing is not the same as nobody trying, and reporting 0% would tell
    // an instructor their learners are struggling when none have sat the quiz.
    expect($stats->passRate())->toBeNull()
        ->and($stats->gradedAttempts)->toBe(0);
});

it('computes a pass rate from graded attempts', function () {
    [, , $lesson] = statsFixture();
    attempt($lesson, true);
    attempt($lesson, true);
    attempt($lesson, false);

    $stats = app(AssessmentStatsPort::class)->passRateForLessons([$lesson->id]);

    expect($stats->gradedAttempts)->toBe(3)
        ->and($stats->passedAttempts)->toBe(2)
        ->and($stats->passRate())->toBe(67);
});

it('ignores attempts with no pass/fail outcome', function () {
    [, , $lesson] = statsFixture();
    attempt($lesson, true);
    // In progress, and an assessment with no pass mark: both leave `passed` null, and neither can
    // contribute to a pass rate in either direction.
    attempt($lesson, null, AttemptStatus::InProgress->value);
    attempt($lesson, null);

    $stats = app(AssessmentStatsPort::class)->passRateForLessons([$lesson->id]);

    expect($stats->gradedAttempts)->toBe(1)
        ->and($stats->passRate())->toBe(100);
});

it('returns an empty result for no lessons without querying', function () {
    $stats = app(AssessmentStatsPort::class)->passRateForLessons([]);

    expect($stats->gradedAttempts)->toBe(0)
        ->and($stats->passRate())->toBeNull();
});

it('counts only the lessons it was asked about', function () {
    [, , $lesson] = statsFixture();
    [, , $otherLesson] = statsFixture();
    attempt($lesson, true);
    attempt($otherLesson, false);
    attempt($otherLesson, false);

    // No cross-course bleed: a course's pass rate must not be affected by a course the caller
    // may not even be able to see.
    $stats = app(AssessmentStatsPort::class)->passRateForLessons([$lesson->id]);

    expect($stats->gradedAttempts)->toBe(1)
        ->and($stats->passRate())->toBe(100);
});

it('surfaces the pass rate on the instructor course endpoint', function () {
    [$course, $instructor, $lesson] = statsFixture();
    attempt($lesson, true);
    attempt($lesson, false);

    $this->actingAs($instructor, 'sanctum')
        ->getJson("/api/v1/teach/courses/{$course->public_id}")
        ->assertOk()
        ->assertJsonPath('data.stats.assessment_pass_rate', 50)
        ->assertJsonPath('data.stats.graded_attempts', 2);
});

it('reports an unavailable pass rate rather than zero on the endpoint', function () {
    [$course, $instructor] = statsFixture();

    $this->actingAs($instructor, 'sanctum')
        ->getJson("/api/v1/teach/courses/{$course->public_id}")
        ->assertOk()
        ->assertJsonPath('data.stats.assessment_pass_rate', null)
        ->assertJsonPath('data.stats.graded_attempts', 0);
});

it('keeps the existing stats fields intact', function () {
    [$course, $instructor] = statsFixture();

    $this->actingAs($instructor, 'sanctum')
        ->getJson("/api/v1/teach/courses/{$course->public_id}")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'stats' => ['enrollments', 'completions', 'avg_progress', 'sections', 'lessons'],
            ],
        ]);
});
