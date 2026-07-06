<?php

use App\Domains\Identity\Models\User;
use App\Domains\Learning\Actions\Enrollment\GrantEnrollmentAction;
use App\Domains\Learning\Enums\EnrollmentSource;
use App\Domains\Learning\Events\CourseCompleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
require_once __DIR__.'/Helpers.php';

it('completes the course when the only lesson is completed and emits CourseCompleted', function () {
    [$course, $section, $lessons] = publishedCourseWithLessons(1);
    $lesson = $lessons->first();

    $user = User::factory()->create();
    app(GrantEnrollmentAction::class)->execute($user, $course, EnrollmentSource::Free);
    Sanctum::actingAs($user);

    Event::fake([CourseCompleted::class]);

    $res = $this->postJson("/api/v1/lessons/{$lesson->public_id}/progress", ['status' => 'completed'])->assertOk();

    expect($res->json('data.course_progress_percentage'))->toBe(100);
    Event::assertDispatched(CourseCompleted::class);
});

it('is idempotent when re-recording completion', function () {
    [$course, $section, $lessons] = publishedCourseWithLessons(2);
    $user = User::factory()->create();
    app(GrantEnrollmentAction::class)->execute($user, $course, EnrollmentSource::Free);
    Sanctum::actingAs($user);

    $first = $lessons->first();
    $this->postJson("/api/v1/lessons/{$first->public_id}/progress", ['status' => 'completed'])->assertOk();
    $res = $this->postJson("/api/v1/lessons/{$first->public_id}/progress", ['status' => 'completed'])->assertOk();

    expect($res->json('data.course_progress_percentage'))->toBe(50); // 1 of 2
});
