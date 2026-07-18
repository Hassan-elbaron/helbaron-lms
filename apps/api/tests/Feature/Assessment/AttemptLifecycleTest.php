<?php

use App\Domains\Assessment\Enums\AttemptStatus;
use App\Domains\Assessment\Enums\FeedbackMode;
use App\Domains\Assessment\Enums\QuestionType;
use App\Domains\Assessment\Models\Assessment;
use App\Domains\Assessment\Models\AssessmentAttempt;
use App\Domains\Assessment\Models\AssessmentQuestion;
use App\Domains\Assessment\Models\QuestionOption;
use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** A published, two-question assessment worth 3 points. */
function attemptAssessment(array $overrides = []): Assessment
{
    $assessment = Assessment::factory()->published()->create($overrides + ['passing_score' => 50]);

    $q1 = AssessmentQuestion::factory()->worth(2)->create([
        'assessment_id' => $assessment->id,
        'type' => QuestionType::SingleChoice->value,
        'position' => 0,
    ]);
    QuestionOption::factory()->correct()->create(['question_id' => $q1->id, 'label' => 'Right', 'position' => 0]);
    QuestionOption::factory()->create(['question_id' => $q1->id, 'label' => 'Wrong', 'position' => 1]);

    $q2 = AssessmentQuestion::factory()->worth(1)->create([
        'assessment_id' => $assessment->id,
        'type' => QuestionType::ShortAnswer->value,
        'position' => 1,
    ]);
    QuestionOption::factory()->accepting('mitochondria')->create(['question_id' => $q2->id]);

    return $assessment->refresh();
}

function correctOptionId(Assessment $assessment): string
{
    return (string) $assessment->questions()->first()->options()->where('is_correct', true)->first()->public_id;
}

it('runs a full attempt and scores it', function () {
    $learner = User::factory()->create();
    $assessment = attemptAssessment();
    $questions = $assessment->questions()->get();

    $start = $this->actingAs($learner, 'sanctum')
        ->postJson("/api/v1/assessments/{$assessment->public_id}/attempts")
        ->assertCreated();

    $attemptId = $start->json('data.id');

    // Answer the first question correctly, the second wrongly.
    $this->actingAs($learner, 'sanctum')->putJson("/api/v1/attempts/{$attemptId}/answers", [
        'question_id' => $questions[0]->public_id,
        'response' => ['option_ids' => [correctOptionId($assessment)]],
    ])->assertOk();

    $this->actingAs($learner, 'sanctum')->putJson("/api/v1/attempts/{$attemptId}/answers", [
        'question_id' => $questions[1]->public_id,
        'response' => ['text' => 'ribosome'],
    ])->assertOk();

    $result = $this->actingAs($learner, 'sanctum')
        ->postJson("/api/v1/attempts/{$attemptId}/submit")
        ->assertOk();

    // 2 of 3 points → 66.67%, above the 50% pass mark.
    // toEqual, not toBe: JSON serialises a whole float as an int, so 2.0 arrives as 2.
    expect($result->json('data.result.score'))->toEqual(2.0)
        ->and($result->json('data.result.max_score'))->toEqual(3.0)
        ->and($result->json('data.result.passed'))->toBeTrue()
        ->and($result->json('data.status'))->toBe(AttemptStatus::Graded->value);
});

it('never exposes the answer key before the attempt is graded', function () {
    $learner = User::factory()->create();
    $assessment = attemptAssessment(['feedback_mode' => FeedbackMode::AfterSubmit->value]);

    $attemptId = $this->actingAs($learner, 'sanctum')
        ->postJson("/api/v1/assessments/{$assessment->public_id}/attempts")
        ->json('data.id');

    $inProgress = $this->actingAs($learner, 'sanctum')
        ->getJson("/api/v1/attempts/{$attemptId}")
        ->assertOk();

    // The whole point: an in-progress paper must not tell the learner which option is right.
    $options = $inProgress->json('data.questions.0.question.options');
    foreach ($options as $option) {
        expect($option)->not->toHaveKey('is_correct')
            ->and($option)->not->toHaveKey('value');
    }
    expect($inProgress->json('data.questions.0.question.explanation'))->toBeNull()
        ->and($inProgress->json('data.result'))->toBeNull();

    $this->actingAs($learner, 'sanctum')->postJson("/api/v1/attempts/{$attemptId}/submit")->assertOk();

    $graded = $this->actingAs($learner, 'sanctum')->getJson("/api/v1/attempts/{$attemptId}")->assertOk();
    expect($graded->json('data.questions.0.question.options.0'))->toHaveKey('is_correct');
});

it('withholds the key entirely when feedback mode is never', function () {
    $learner = User::factory()->create();
    $assessment = attemptAssessment(['feedback_mode' => FeedbackMode::Never->value]);

    $attemptId = $this->actingAs($learner, 'sanctum')
        ->postJson("/api/v1/assessments/{$assessment->public_id}/attempts")->json('data.id');
    $this->actingAs($learner, 'sanctum')->postJson("/api/v1/attempts/{$attemptId}/submit")->assertOk();

    $graded = $this->actingAs($learner, 'sanctum')->getJson("/api/v1/attempts/{$attemptId}")->assertOk();

    // The learner still learns their score — only the key stays hidden.
    expect($graded->json('data.questions.0.question.options.0'))->not->toHaveKey('is_correct')
        ->and($graded->json('data.result.score'))->not->toBeNull();
});

it('resumes an open attempt instead of consuming another one', function () {
    $learner = User::factory()->create();
    $assessment = attemptAssessment(['max_attempts' => 1]);

    $first = $this->actingAs($learner, 'sanctum')
        ->postJson("/api/v1/assessments/{$assessment->public_id}/attempts")->json('data.id');

    // A refreshed tab must not burn the learner's only attempt.
    $second = $this->actingAs($learner, 'sanctum')
        ->postJson("/api/v1/assessments/{$assessment->public_id}/attempts")->json('data.id');

    expect($second)->toBe($first)
        ->and(AssessmentAttempt::count())->toBe(1);
});

it('enforces the attempt limit once an attempt is finished', function () {
    $learner = User::factory()->create();
    $assessment = attemptAssessment(['max_attempts' => 1]);

    $attemptId = $this->actingAs($learner, 'sanctum')
        ->postJson("/api/v1/assessments/{$assessment->public_id}/attempts")->json('data.id');
    $this->actingAs($learner, 'sanctum')->postJson("/api/v1/attempts/{$attemptId}/submit")->assertOk();

    $this->actingAs($learner, 'sanctum')
        ->postJson("/api/v1/assessments/{$assessment->public_id}/attempts")
        ->assertStatus(422);
});

it('refuses answers for questions this sitting did not serve', function () {
    $learner = User::factory()->create();
    $assessment = attemptAssessment();
    $foreign = attemptAssessment();

    $attemptId = $this->actingAs($learner, 'sanctum')
        ->postJson("/api/v1/assessments/{$assessment->public_id}/attempts")->json('data.id');

    $this->actingAs($learner, 'sanctum')->putJson("/api/v1/attempts/{$attemptId}/answers", [
        'question_id' => $foreign->questions()->first()->public_id,
        'response' => ['option_ids' => [correctOptionId($foreign)]],
    ])->assertStatus(422);
});

it('keeps an attempt private to the learner who sat it', function () {
    $learner = User::factory()->create();
    $stranger = User::factory()->create();
    $assessment = attemptAssessment();

    $attemptId = $this->actingAs($learner, 'sanctum')
        ->postJson("/api/v1/assessments/{$assessment->public_id}/attempts")->json('data.id');

    $this->actingAs($stranger, 'sanctum')->getJson("/api/v1/attempts/{$attemptId}")->assertForbidden();
    $this->actingAs($stranger, 'sanctum')->postJson("/api/v1/attempts/{$attemptId}/submit")->assertForbidden();
});

it('will not start an attempt on an unpublished assessment', function () {
    $learner = User::factory()->create();
    $draft = attemptAssessment();
    $draft->forceFill(['status' => 'draft'])->save();

    $this->actingAs($learner, 'sanctum')
        ->postJson("/api/v1/assessments/{$draft->public_id}/attempts")
        ->assertStatus(422);
});

it('scores an expired attempt but records that it expired', function () {
    $learner = User::factory()->create();
    $assessment = attemptAssessment();

    $attempt = AssessmentAttempt::factory()->expired()->serving(
        $assessment->questions()->pluck('public_id')->all(),
    )->create(['assessment_id' => $assessment->id, 'user_id' => $learner->id]);

    $response = $this->actingAs($learner, 'sanctum')
        ->postJson("/api/v1/attempts/{$attempt->public_id}/submit")
        ->assertOk();

    // Expired must survive grading — it is the only record that the learner was cut off.
    expect($response->json('data.status'))->toBe(AttemptStatus::Expired->value)
        ->and($response->json('data.result.max_score'))->toEqual(3.0);
});

it('applies a penalty only to wrong answers that were actually attempted', function () {
    $learner = User::factory()->create();
    $assessment = attemptAssessment(['negative_marking' => true]);
    $assessment->questions()->update(['negative_points' => 1]);
    $questions = $assessment->questions()->get();

    $attemptId = $this->actingAs($learner, 'sanctum')
        ->postJson("/api/v1/assessments/{$assessment->public_id}/attempts")->json('data.id');

    // Answer one wrongly; leave the other untouched.
    $this->actingAs($learner, 'sanctum')->putJson("/api/v1/attempts/{$attemptId}/answers", [
        'question_id' => $questions[0]->public_id,
        'response' => ['option_ids' => ['not-a-real-option']],
    ])->assertOk();

    $result = $this->actingAs($learner, 'sanctum')
        ->postJson("/api/v1/attempts/{$attemptId}/submit")->assertOk();

    // -1 for the wrong answer, 0 (not -1) for the unanswered one, floored at zero overall.
    expect($result->json('data.result.score'))->toEqual(0.0)
        ->and($result->json('data.result.passed'))->toBeFalse();
});
