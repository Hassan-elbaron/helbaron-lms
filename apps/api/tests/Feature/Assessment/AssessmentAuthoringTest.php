<?php

use App\Domains\Assessment\Database\Seeders\AssessmentSeeder;
use App\Domains\Assessment\Enums\AssessmentStatus;
use App\Domains\Assessment\Enums\QuestionType;
use App\Domains\Assessment\Models\Assessment;
use App\Domains\Catalog\Enums\CourseStatus;
use App\Domains\Catalog\Models\Course;
use App\Platform\Identity\Database\Seeders\IdentitySeeder;
use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as SpatieRole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(IdentitySeeder::class);
    $this->seed(AssessmentSeeder::class);
});

function assessmentUser(string ...$roles): User
{
    $user = User::factory()->create();

    foreach ($roles as $role) {
        $user->assignRole(SpatieRole::findByName($role, 'web'));
    }

    return $user;
}

function assessmentCourse(?User $trainer = null): Course
{
    $course = Course::factory()->create(['status' => CourseStatus::Draft]);

    if ($trainer !== null) {
        $course->syncTrainers([$trainer->id]);
    }

    return $course;
}

/** A question payload that satisfies QuestionShapeGuard. */
function singleChoicePayload(string $correct = 'Paris'): array
{
    return [
        'type' => QuestionType::SingleChoice->value,
        'prompt' => '<p>Capital of France?</p>',
        'points' => 2,
        'options' => [
            ['label' => $correct, 'is_correct' => true],
            ['label' => 'Berlin', 'is_correct' => false],
        ],
    ];
}

it('lets an assigned instructor create an assessment on their own course', function () {
    $instructor = assessmentUser('instructor');
    $course = assessmentCourse($instructor);

    $this->actingAs($instructor, 'sanctum')
        ->postJson("/api/v1/admin/courses/{$course->public_id}/assessments", ['title' => 'Module 1 quiz'])
        ->assertCreated()
        ->assertJsonPath('data.status', AssessmentStatus::Draft->value);

    expect(Assessment::where('course_id', $course->id)->count())->toBe(1);
});

it('hides courses the instructor does not train behind a 404', function () {
    $owner = assessmentUser('instructor');
    $outsider = assessmentUser('instructor');
    $course = assessmentCourse($owner);

    // 404, not 403: a 403 would confirm the course exists, letting an instructor enumerate courses.
    $this->actingAs($outsider, 'sanctum')
        ->postJson("/api/v1/admin/courses/{$course->public_id}/assessments", ['title' => 'Sneaky'])
        ->assertNotFound();

    expect(Assessment::count())->toBe(0);
});

it('blocks horizontal privilege escalation between instructors', function () {
    $owner = assessmentUser('instructor');
    $attacker = assessmentUser('instructor');
    assessmentCourse($attacker); // the attacker legitimately trains something else

    $assessment = Assessment::factory()->create(['course_id' => assessmentCourse($owner)->id]);

    $this->actingAs($attacker, 'sanctum')
        ->putJson("/api/v1/admin/assessments/{$assessment->public_id}", ['title' => 'Hijacked'])
        ->assertForbidden();

    $this->actingAs($attacker, 'sanctum')
        ->getJson("/api/v1/admin/assessments/{$assessment->public_id}")
        ->assertForbidden();

    expect($assessment->refresh()->title)->not->toBe('Hijacked');
});

it('refuses to attach a question to an assessment the caller does not own', function () {
    $owner = assessmentUser('instructor');
    $attacker = assessmentUser('instructor');
    $assessment = Assessment::factory()->create(['course_id' => assessmentCourse($owner)->id]);

    $this->actingAs($attacker, 'sanctum')
        ->postJson("/api/v1/admin/assessments/{$assessment->public_id}/questions", singleChoicePayload())
        ->assertForbidden();

    expect($assessment->questions()->count())->toBe(0);
});

it('rejects a single-choice question with two correct answers', function () {
    $instructor = assessmentUser('instructor');
    $assessment = Assessment::factory()->create(['course_id' => assessmentCourse($instructor)->id]);

    $payload = singleChoicePayload();
    $payload['options'][1]['is_correct'] = true;

    // Such a question is unanswerable — every learner would lose its marks.
    $this->actingAs($instructor, 'sanctum')
        ->postJson("/api/v1/admin/assessments/{$assessment->public_id}/questions", $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors('options');
});

it('rejects a short-answer question with no accepted answer', function () {
    $instructor = assessmentUser('instructor');
    $assessment = Assessment::factory()->create(['course_id' => assessmentCourse($instructor)->id]);

    $this->actingAs($instructor, 'sanctum')
        ->postJson("/api/v1/admin/assessments/{$assessment->public_id}/questions", [
            'type' => QuestionType::ShortAnswer->value,
            'prompt' => '<p>Name the process.</p>',
            'options' => [['value' => 'photosynthesis', 'is_correct' => false]],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('options');
});

it('will not publish an assessment with no questions', function () {
    $instructor = assessmentUser('instructor');
    $assessment = Assessment::factory()->create(['course_id' => assessmentCourse($instructor)->id]);

    $this->actingAs($instructor, 'sanctum')
        ->postJson("/api/v1/admin/assessments/{$assessment->public_id}/status", ['status' => 'published'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');

    expect($assessment->refresh()->status)->toBe(AssessmentStatus::Draft);
});

it('publishes once a valid question exists', function () {
    $instructor = assessmentUser('instructor');
    $assessment = Assessment::factory()->create(['course_id' => assessmentCourse($instructor)->id]);

    $this->actingAs($instructor, 'sanctum')
        ->postJson("/api/v1/admin/assessments/{$assessment->public_id}/questions", singleChoicePayload())
        ->assertCreated();

    $this->actingAs($instructor, 'sanctum')
        ->postJson("/api/v1/admin/assessments/{$assessment->public_id}/status", ['status' => 'published'])
        ->assertOk();

    expect($assessment->refresh()->status)->toBe(AssessmentStatus::Published);
});

it('always allows un-publishing, so a broken assessment can be pulled immediately', function () {
    $instructor = assessmentUser('instructor');
    $assessment = Assessment::factory()->published()->create(['course_id' => assessmentCourse($instructor)->id]);

    $this->actingAs($instructor, 'sanctum')
        ->postJson("/api/v1/admin/assessments/{$assessment->public_id}/status", ['status' => 'draft'])
        ->assertOk();

    expect($assessment->refresh()->status)->toBe(AssessmentStatus::Draft);
});

it('does not grant instructors the global assessment permission', function () {
    // Regression guard for the Step 2 defect: a blanket grant would satisfy the gate's first
    // branch and hand every instructor access to every course's assessments.
    $instructor = assessmentUser('instructor');

    expect($instructor->can('assessment.manage'))->toBeFalse()
        ->and(SpatieRole::findByName('admin', 'web')->hasPermissionTo('assessment.manage'))->toBeTrue();
});

it('lets an admin manage assessments on any course', function () {
    $admin = assessmentUser('admin');
    $assessment = Assessment::factory()->create(['course_id' => assessmentCourse()->id]);

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/admin/assessments/{$assessment->public_id}", ['title' => 'Renamed'])
        ->assertOk();

    expect($assessment->refresh()->title)->toBe('Renamed');
});
