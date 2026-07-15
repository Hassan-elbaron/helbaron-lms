<?php

use App\Contexts\Learning\Actions\Enrollment\GrantEnrollmentAction;
use App\Contexts\Learning\Enums\EnrollmentSource;
use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
require_once __DIR__.'/Helpers.php';

it('toggles bookmarks and upserts notes, and resumes via continue-learning', function () {
    [$course, $section, $lessons] = publishedCourseWithLessons(2);
    $lesson = $lessons->first();
    $user = User::factory()->create();
    app(GrantEnrollmentAction::class)->executeByUserId($user->id, $course->id, EnrollmentSource::Free);
    Sanctum::actingAs($user);

    $this->postJson("/api/v1/lessons/{$lesson->public_id}/bookmark")->assertOk()->assertJsonPath('data.bookmarked', true);
    $this->postJson("/api/v1/lessons/{$lesson->public_id}/bookmark")->assertOk()->assertJsonPath('data.bookmarked', false);

    $this->postJson("/api/v1/lessons/{$lesson->public_id}/notes", ['body' => 'Remember this'])
        ->assertOk()->assertJsonPath('data.body', 'Remember this');

    $cont = $this->getJson('/api/v1/continue-learning')->assertOk();
    expect($cont->json('data.0.next_lesson.id'))->toBe($lesson->public_id);
});
