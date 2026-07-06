<?php

use App\Domains\Identity\Models\User;
use App\Domains\Learning\Actions\Enrollment\GrantEnrollmentAction;
use App\Domains\Learning\Enums\EnrollmentSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
require_once __DIR__.'/Helpers.php';

it('locks a lesson until its prerequisite is completed', function () {
    [$course, $section, $lessons] = publishedCourseWithLessons(2);
    $a = $lessons->get(0);
    $b = $lessons->get(1);
    $b->prerequisites()->attach($a->id);

    $user = User::factory()->create();
    app(GrantEnrollmentAction::class)->execute($user, $course, EnrollmentSource::Free);
    Sanctum::actingAs($user);

    $this->getJson("/api/v1/lessons/{$b->public_id}")
        ->assertStatus(403)->assertJsonPath('error.code', 'LEARNING_LESSON_LOCKED');

    $this->postJson("/api/v1/lessons/{$a->public_id}/progress", ['status' => 'completed'])->assertOk();

    $this->getJson("/api/v1/lessons/{$b->public_id}")->assertOk();
});
