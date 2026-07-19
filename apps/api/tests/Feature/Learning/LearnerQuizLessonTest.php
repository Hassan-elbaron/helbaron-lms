<?php

use App\Contexts\Learning\Models\Enrollment;
use App\Domains\Assessment\Enums\QuestionType;
use App\Domains\Assessment\Models\Assessment;
use App\Domains\Assessment\Models\AssessmentQuestion;
use App\Domains\Assessment\Models\QuestionOption;
use App\Domains\Authoring\Enums\LessonType;
use App\Domains\Authoring\Enums\PublishState;
use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Domains\Catalog\Enums\CourseStatus;
use App\Domains\Catalog\Models\Course;
use App\Platform\Identity\Database\Seeders\IdentitySeeder;
use App\Platform\Identity\Models\User;
use App\Platform\Shared\Curriculum\Contracts\CurriculumReadPort;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(IdentitySeeder::class);
});

/**
 * The learner-facing half of the quiz wiring: what a lesson payload exposes about an attached
 * assessment, and — more importantly — what it must never expose.
 */
function quizFixture(string $assessmentStatus = 'published', ?LessonType $type = null): array
{
    $course = Course::factory()->create(['status' => CourseStatus::Published]);
    $section = Section::factory()->create([
        'course_id' => $course->id,
        'publish_state' => PublishState::Published->value,
    ]);

    $assessment = Assessment::factory()->create([
        'course_id' => $course->id,
        'title' => 'Module quiz',
        'status' => $assessmentStatus,
    ]);

    $question = AssessmentQuestion::factory()->create([
        'assessment_id' => $assessment->id,
        'type' => QuestionType::SingleChoice->value,
        'prompt' => '<p>Capital of France?</p>',
    ]);
    QuestionOption::factory()->correct()->create(['question_id' => $question->id, 'label' => 'Paris']);
    QuestionOption::factory()->create(['question_id' => $question->id, 'label' => 'Berlin', 'position' => 1]);

    $lesson = Lesson::factory()->create([
        'section_id' => $section->id,
        'type' => ($type ?? LessonType::Quiz)->value,
        'publish_state' => PublishState::Published->value,
        'assessment_id' => $assessment->id,
    ]);

    $learner = User::factory()->create();
    Enrollment::factory()->create(['user_id' => $learner->id, 'course_id' => $course->id]);

    return [$learner, $lesson, $assessment];
}

it('carries assessmentId on the LessonRef for a quiz lesson', function () {
    [, $lesson, $assessment] = quizFixture();

    $ref = app(CurriculumReadPort::class)->findLessonByPublicId($lesson->public_id);

    expect($ref)->not->toBeNull()
        ->and($ref->assessmentId)->toBe($assessment->id);
});

it('leaves assessmentId null for a non-quiz lesson even if the column is set', function () {
    // Gating on the lesson TYPE, not just the column, is what stops a stray reference on an
    // article reaching the learner payload.
    [, $lesson] = quizFixture('published', LessonType::Article);

    $ref = app(CurriculumReadPort::class)->findLessonByPublicId($lesson->public_id);

    expect($ref->assessmentId)->toBeNull();
});

it('exposes a safe assessment reference on a published quiz lesson', function () {
    [$learner, $lesson, $assessment] = quizFixture();

    $response = $this->actingAs($learner, 'sanctum')
        ->getJson("/api/v1/lessons/{$lesson->public_id}")
        ->assertOk();

    $response->assertJsonPath('data.assessment.id', $assessment->public_id)
        ->assertJsonPath('data.assessment.title', 'Module quiz')
        ->assertJsonPath('data.assessment.question_count', 1)
        ->assertJsonPath('data.assessment.version', 1);

    // Exactly four fields — no status, no settings, nothing instructor-only.
    expect(array_keys($response->json('data.assessment')))
        ->toEqualCanonicalizing(['id', 'title', 'question_count', 'version']);
});

it('hides a draft assessment exactly as if none were attached', function () {
    [$learner, $lesson] = quizFixture('draft');

    // An author mid-edit must not have their unfinished quiz appear to learners, and no draft id
    // should leave the server at all.
    $this->actingAs($learner, 'sanctum')
        ->getJson("/api/v1/lessons/{$lesson->public_id}")
        ->assertOk()
        ->assertJsonPath('data.assessment', null);
});

it('hides an archived assessment', function () {
    [$learner, $lesson] = quizFixture('archived');

    $this->actingAs($learner, 'sanctum')
        ->getJson("/api/v1/lessons/{$lesson->public_id}")
        ->assertOk()
        ->assertJsonPath('data.assessment', null);
});

it('returns null for a quiz lesson with nothing attached', function () {
    [$learner, $lesson] = quizFixture();
    $lesson->forceFill(['assessment_id' => null])->save();

    $this->actingAs($learner, 'sanctum')
        ->getJson("/api/v1/lessons/{$lesson->public_id}")
        ->assertOk()
        ->assertJsonPath('data.assessment', null);
});

it('does not expose an assessment on a non-quiz lesson', function () {
    [$learner, $lesson] = quizFixture('published', LessonType::Article);

    $this->actingAs($learner, 'sanctum')
        ->getJson("/api/v1/lessons/{$lesson->public_id}")
        ->assertOk()
        ->assertJsonPath('data.assessment', null);
});

it('leaks no answer key through the lesson payload', function () {
    [$learner, $lesson] = quizFixture();

    $body = $this->actingAs($learner, 'sanctum')
        ->getJson("/api/v1/lessons/{$lesson->public_id}")
        ->assertOk()
        ->getContent();

    // The lesson payload advertises that a quiz exists; questions and the key come only from the
    // attempt endpoints, which apply their own entitlement rules.
    expect($body)->not->toContain('is_correct')
        ->and($body)->not->toContain('Paris')
        ->and($body)->not->toContain('passing_score')
        ->and($body)->not->toContain('feedback_mode');
});

it('keeps the existing lesson payload shape backward compatible', function () {
    [$learner, $lesson] = quizFixture('published', LessonType::Article);

    $this->actingAs($learner, 'sanctum')
        ->getJson("/api/v1/lessons/{$lesson->public_id}")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id', 'title', 'type', 'content', 'is_preview', 'playback',
                'progress' => ['status', 'position_seconds'],
                'bookmarked', 'note',
                'navigation' => ['previous', 'next'],
                'assessment',
            ],
        ]);
});

it('still denies a learner who is not enrolled', function () {
    [, $lesson] = quizFixture();
    $stranger = User::factory()->create();

    // The new reference must not become a way around lesson access control.
    $this->actingAs($stranger, 'sanctum')
        ->getJson("/api/v1/lessons/{$lesson->public_id}")
        ->assertForbidden();
});
