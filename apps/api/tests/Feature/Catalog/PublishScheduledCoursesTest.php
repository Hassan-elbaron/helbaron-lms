<?php

use App\Domains\Authoring\Enums\LessonType;
use App\Domains\Authoring\Enums\PublishState;
use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Domains\Catalog\Enums\CourseStatus;
use App\Domains\Catalog\Events\CoursePublished;
use App\Domains\Catalog\Models\Course;
use App\Platform\Identity\Database\Seeders\IdentitySeeder;
use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role as SpatieRole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(IdentitySeeder::class);
});

/** A course scheduled for a moment that has already passed, so the command picks it up. */
function scheduledCourse(bool $ready): Course
{
    $course = Course::factory()->create([
        'status' => CourseStatus::Scheduled->value,
        'scheduled_publish_at' => now()->subMinute(),
        'description' => 'A real description.',
        'thumbnail_path' => 'courses/thumb.jpg',
    ]);

    $instructor = User::factory()->create();
    $instructor->assignRole(SpatieRole::findByName('instructor', 'web'));
    $course->syncTrainers([$instructor->id]);

    $section = Section::factory()->create([
        'course_id' => $course->id,
        'publish_state' => PublishState::Published->value,
    ]);

    Lesson::factory()->create([
        'section_id' => $section->id,
        'type' => LessonType::Article->value,
        'publish_state' => PublishState::Published->value,
        // A hollow published lesson is the readiness blocker; real content clears it.
        'content' => $ready ? ['html' => '<p>Body</p>'] : null,
    ]);

    return $course;
}

it('publishes a scheduled course that passes readiness', function () {
    $course = scheduledCourse(ready: true);

    $this->artisan('courses:publish-scheduled')->assertSuccessful();

    expect($course->fresh()->status)->toBe(CourseStatus::Published)
        ->and($course->fresh()->scheduled_publish_at)->toBeNull();
});

it('leaves a blocked course scheduled rather than corrupting its state', function () {
    $course = scheduledCourse(ready: false);

    $this->artisan('courses:publish-scheduled')->assertSuccessful();

    expect($course->fresh()->status)->toBe(CourseStatus::Scheduled);
});

/*
 * The silent loop.
 *
 * The command used to swallow CoursePublishBlockedException and only increment a counter, so the
 * whole trace of a missed launch was "0 published, 7 pending (not ready)" — no course id, no reason
 * — re-emitted every minute forever with nothing alerting. An author waiting on a scheduled release
 * and an operator reading the log had no way to learn which courses were stuck or why.
 */
it('logs the course public id and the blocker codes when a scheduled publish is refused', function () {
    $course = scheduledCourse(ready: false);

    $captured = [];
    Log::shouldReceive('warning')
        ->once()
        ->with('Scheduled course publish blocked by readiness rules.', Mockery::on(
            function (array $context) use (&$captured): bool {
                $captured = $context;

                return true;
            },
        ));

    $this->artisan('courses:publish-scheduled')->assertSuccessful();

    expect($captured['course_public_id'])->toBe((string) $course->public_id)
        // Stable codes, so an operator can grep and alert on this without matching prose that gets
        // reworded.
        ->and($captured['blockers'])->toContain('lesson.empty_content')
        ->and($captured['reason'])->toBeString()->not->toBe('');
});

it('names the blocked course on the console as well as in the log', function () {
    $course = scheduledCourse(ready: false);

    $this->artisan('courses:publish-scheduled')
        ->expectsOutputToContain((string) $course->public_id)
        ->expectsOutputToContain('lesson.empty_content')
        ->assertSuccessful();
});

it('reports nothing when no scheduled course is blocked', function () {
    $course = scheduledCourse(ready: true);

    Log::shouldReceive('warning')->never();
    Log::shouldReceive('error')->never();

    $this->artisan('courses:publish-scheduled')->assertSuccessful();

    // A11.3: without this the test only proved "no warning was logged for a course that was never
    // going to be blocked" — it would stay green if the command silently published nothing at all.
    expect($course->fresh()->status)->toBe(CourseStatus::Published);
});

/*
 * -- A7: one poison course must not stall every scheduled publish behind it -----------------------
 *
 * The catch handled only CoursePublishBlockedException, so anything else — a CourseTransitionException
 * from the state machine, a listener throwing on the CoursePublished event (dispatched OUTSIDE the
 * publish transaction), a QueryException, a deadlock — escaped the loop and aborted the whole run.
 * The ordering is deterministic, so the same course poisoned every subsequent tick and every course
 * behind it missed its launch date forever. No test created a second course, so nothing noticed.
 */
it('publishes the courses behind one that fails unexpectedly', function () {
    $poison = scheduledCourse(ready: true);
    $healthy = scheduledCourse(ready: true);

    // Make the FIRST course by scheduled time blow up in a way the old catch did not cover: a
    // listener on the CoursePublished event, which fires outside the publish transaction.
    Event::listen(CoursePublished::class, function (CoursePublished $event) use ($poison): void {
        if ((int) $event->course->getKey() === (int) $poison->getKey()) {
            throw new RuntimeException('listener exploded');
        }
    });

    $this->artisan('courses:publish-scheduled')->assertExitCode(1);

    // The healthy course behind it still went out.
    expect($healthy->fresh()->status)->toBe(CourseStatus::Published);
});

it('logs an unexpected failure at error level with the course id', function () {
    $poison = scheduledCourse(ready: true);

    Event::listen(CoursePublished::class, function () {
        throw new RuntimeException('listener exploded');
    });

    $captured = [];
    Log::shouldReceive('warning')->zeroOrMoreTimes();
    Log::shouldReceive('error')
        ->once()
        ->with('Scheduled course publish failed unexpectedly.', Mockery::on(
            function (array $context) use (&$captured): bool {
                $captured = $context;

                return true;
            },
        ));

    $this->artisan('courses:publish-scheduled');

    // ERROR, not WARNING: a readiness block is expected and self-healing, this is neither.
    expect($captured['course_public_id'])->toBe((string) $poison->public_id)
        ->and($captured['exception'])->toBe(RuntimeException::class);
});

it('still exits successfully when the only problem is readiness', function () {
    scheduledCourse(ready: false);
    $healthy = scheduledCourse(ready: true);

    // A readiness block must not page anyone: an author has simply not finished writing, and the
    // course republishes itself on the next tick once they do.
    $this->artisan('courses:publish-scheduled')->assertExitCode(0);

    expect($healthy->fresh()->status)->toBe(CourseStatus::Published);
});

it('publishes the courses behind one that is readiness-blocked', function () {
    scheduledCourse(ready: false);
    $healthy = scheduledCourse(ready: true);

    $this->artisan('courses:publish-scheduled')->assertSuccessful();

    expect($healthy->fresh()->status)->toBe(CourseStatus::Published);
});

it('reports every outcome in its summary line', function () {
    scheduledCourse(ready: true);
    scheduledCourse(ready: false);

    $this->artisan('courses:publish-scheduled')
        ->expectsOutputToContain('Published: 1')
        ->expectsOutputToContain('Pending (not ready): 1')
        ->expectsOutputToContain('Failed unexpectedly: 0')
        ->assertSuccessful();
});
