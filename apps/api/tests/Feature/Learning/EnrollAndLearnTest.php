<?php

use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
require_once __DIR__.'/Helpers.php';

it('enrolls, lists my-learning, and returns the learner curriculum', function () {
    [$course, $section, $lessons] = publishedCourseWithLessons(2);
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson("/api/v1/courses/{$course->public_id}/enroll")->assertCreated();

    $this->getJson('/api/v1/my-learning')->assertOk()->assertJsonPath('data.0.progress_percentage', 0);

    $learn = $this->getJson("/api/v1/courses/{$course->public_id}/learn")->assertOk();
    expect($learn->json('data.sections.0.lessons'))->toHaveCount(2)
        ->and($learn->json('data.enrollment.status'))->toBe('active');
});

it('returns 403 when learning a course you are not enrolled in', function () {
    [$course] = publishedCourseWithLessons(1);
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/v1/courses/{$course->public_id}/learn")
        ->assertStatus(403)->assertJsonPath('error.code', 'LEARNING_NOT_ENROLLED');
});
