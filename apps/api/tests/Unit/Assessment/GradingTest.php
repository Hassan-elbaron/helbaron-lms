<?php

use App\Domains\Assessment\Enums\QuestionType;
use App\Domains\Assessment\Grading\AnswerNormalizer;
use App\Domains\Assessment\Grading\GraderRegistry;
use App\Domains\Assessment\Grading\Graders\FillInBlankGrader;
use App\Domains\Assessment\Grading\Graders\MultipleChoiceGrader;
use App\Domains\Assessment\Grading\Graders\ShortAnswerGrader;
use App\Domains\Assessment\Grading\Graders\SingleChoiceGrader;
use App\Domains\Assessment\Grading\Graders\TrueFalseGrader;
use App\Domains\Assessment\Models\AssessmentAnswer;
use App\Domains\Assessment\Models\AssessmentQuestion;
use App\Domains\Assessment\Models\QuestionOption;

/**
 * Grading is pure logic over in-memory models — no database. These tests pin the scoring rules a
 * learner's mark depends on.
 */

/** Builds an unsaved question with options, using stable fake public_ids. */
function gradingQuestion(QuestionType $type, array $options, array $config = []): AssessmentQuestion
{
    $question = new AssessmentQuestion(['type' => $type->value, 'config' => $config === [] ? null : $config]);
    $question->setRelation('options', collect($options)->map(function (array $spec, int $i) {
        $option = new QuestionOption([
            'label' => $spec['label'] ?? null,
            'value' => $spec['value'] ?? null,
            'is_correct' => $spec['is_correct'] ?? false,
            'group_index' => $spec['group_index'] ?? 0,
        ]);
        $option->public_id = $spec['id'] ?? "opt-{$i}";

        return $option;
    })->values());

    return $question;
}

function gradingAnswer(?array $response): AssessmentAnswer
{
    return new AssessmentAnswer(['response' => $response]);
}

it('marks a single-choice answer correct only for the exact key', function () {
    $question = gradingQuestion(QuestionType::SingleChoice, [
        ['id' => 'a', 'label' => 'A', 'is_correct' => true],
        ['id' => 'b', 'label' => 'B'],
    ]);
    $grader = new SingleChoiceGrader;

    expect($grader->grade($question, gradingAnswer(['option_ids' => ['a']]))->isFullyCorrect())->toBeTrue()
        ->and($grader->grade($question, gradingAnswer(['option_ids' => ['b']]))->isCorrect)->toBeFalse()
        // Selecting several options on a single-choice question is wrong, not a crash.
        ->and($grader->grade($question, gradingAnswer(['option_ids' => ['a', 'b']]))->isCorrect)->toBeFalse()
        // An unanswered or malformed payload must never throw.
        ->and($grader->grade($question, gradingAnswer(null))->isCorrect)->toBeFalse()
        ->and($grader->grade($question, gradingAnswer(['text' => 'a']))->isCorrect)->toBeFalse();
});

it('grades true/false with the single-choice rule', function () {
    $question = gradingQuestion(QuestionType::TrueFalse, [
        ['id' => 't', 'label' => 'True', 'is_correct' => true],
        ['id' => 'f', 'label' => 'False'],
    ]);

    expect((new TrueFalseGrader)->grade($question, gradingAnswer(['option_ids' => ['t']]))->isFullyCorrect())->toBeTrue()
        ->and((new TrueFalseGrader)->type())->toBe(QuestionType::TrueFalse);
});

it('requires the exact correct set for multiple choice', function () {
    $question = gradingQuestion(QuestionType::MultipleChoice, [
        ['id' => 'a', 'label' => 'A', 'is_correct' => true],
        ['id' => 'b', 'label' => 'B', 'is_correct' => true],
        ['id' => 'c', 'label' => 'C'],
    ]);
    $grader = new MultipleChoiceGrader;

    expect($grader->grade($question, gradingAnswer(['option_ids' => ['a', 'b']]))->isFullyCorrect())->toBeTrue()
        // Partial selection is not credit by default.
        ->and($grader->grade($question, gradingAnswer(['option_ids' => ['a']]))->isCorrect)->toBeFalse()
        // Selecting everything must NOT score full marks.
        ->and($grader->grade($question, gradingAnswer(['option_ids' => ['a', 'b', 'c']]))->isCorrect)->toBeFalse()
        // A duplicated id must not count twice and fake a complete set.
        ->and($grader->grade($question, gradingAnswer(['option_ids' => ['a', 'a']]))->isCorrect)->toBeFalse();
});

it('awards partial credit for multiple choice only when configured, penalising wrong picks', function () {
    $options = [
        ['id' => 'a', 'label' => 'A', 'is_correct' => true],
        ['id' => 'b', 'label' => 'B', 'is_correct' => true],
        ['id' => 'c', 'label' => 'C'],
    ];
    $question = gradingQuestion(QuestionType::MultipleChoice, $options, ['partial_credit' => true]);
    $grader = new MultipleChoiceGrader;

    // One of two correct = half marks.
    expect($grader->grade($question, gradingAnswer(['option_ids' => ['a']]))->ratio)->toBe(0.5)
        // One right + one wrong nets to zero, so shotgunning cannot beat answering carefully.
        ->and($grader->grade($question, gradingAnswer(['option_ids' => ['a', 'c']]))->ratio)->toBe(0.0)
        ->and($grader->grade($question, gradingAnswer(['option_ids' => ['a', 'b']]))->ratio)->toBe(1.0);
});

it('matches short answers after normalisation', function () {
    $question = gradingQuestion(QuestionType::ShortAnswer, [
        ['value' => 'Photosynthesis', 'is_correct' => true],
        ['value' => 'photo synthesis', 'is_correct' => true],
    ]);
    $grader = new ShortAnswerGrader(new AnswerNormalizer);

    expect($grader->grade($question, gradingAnswer(['text' => 'photosynthesis']))->isFullyCorrect())->toBeTrue()
        // Case and surrounding whitespace are not knowledge.
        ->and($grader->grade($question, gradingAnswer(['text' => '  PHOTOSYNTHESIS  ']))->isFullyCorrect())->toBeTrue()
        // A second accepted spelling counts.
        ->and($grader->grade($question, gradingAnswer(['text' => 'photo  synthesis']))->isFullyCorrect())->toBeTrue()
        ->and($grader->grade($question, gradingAnswer(['text' => 'respiration']))->isCorrect)->toBeFalse()
        ->and($grader->grade($question, gradingAnswer(['text' => '   ']))->isCorrect)->toBeFalse();
});

it('honours case sensitivity when the author asks for it', function () {
    $question = gradingQuestion(QuestionType::ShortAnswer, [
        ['value' => 'DNA', 'is_correct' => true],
    ], ['case_sensitive' => true]);
    $grader = new ShortAnswerGrader(new AnswerNormalizer);

    expect($grader->grade($question, gradingAnswer(['text' => 'DNA']))->isFullyCorrect())->toBeTrue()
        ->and($grader->grade($question, gradingAnswer(['text' => 'dna']))->isCorrect)->toBeFalse();
});

it('treats Arabic orthographic variants as the same answer', function () {
    // The learner types a bare alef and no diacritics; the key has hamza and harakat. Marking this
    // wrong would fail a correct answer for typography, which is the bug this normalisation exists
    // to prevent.
    $question = gradingQuestion(QuestionType::ShortAnswer, [
        ['value' => 'إجابة', 'is_correct' => true],
    ]);
    $grader = new ShortAnswerGrader(new AnswerNormalizer);

    expect($grader->grade($question, gradingAnswer(['text' => 'اجابة']))->isFullyCorrect())->toBeTrue();
});

it('maps Arabic-Indic digits onto ASCII', function () {
    $question = gradingQuestion(QuestionType::ShortAnswer, [
        ['value' => '42', 'is_correct' => true],
    ]);

    expect((new ShortAnswerGrader(new AnswerNormalizer))
        ->grade($question, gradingAnswer(['text' => '٤٢']))->isFullyCorrect())->toBeTrue();
});

it('requires every blank by default and scores per blank when configured', function () {
    $options = [
        ['value' => 'carbon', 'is_correct' => true, 'group_index' => 0],
        ['value' => 'dioxide', 'is_correct' => true, 'group_index' => 1],
    ];
    $grader = new FillInBlankGrader(new AnswerNormalizer);

    $strict = gradingQuestion(QuestionType::FillInBlank, $options);
    expect($grader->grade($strict, gradingAnswer(['blanks' => ['0' => 'carbon', '1' => 'dioxide']]))->isFullyCorrect())->toBeTrue()
        ->and($grader->grade($strict, gradingAnswer(['blanks' => ['0' => 'carbon']]))->isCorrect)->toBeFalse();

    $partial = gradingQuestion(QuestionType::FillInBlank, $options, ['partial_credit' => true]);
    expect($grader->grade($partial, gradingAnswer(['blanks' => ['0' => 'carbon']]))->ratio)->toBe(0.5)
        // The denominator comes from the answer key, so omitting blanks cannot inflate the score.
        ->and($grader->grade($partial, gradingAnswer(['blanks' => []]))->ratio)->toBe(0.0);
});

it('resolves a grader for every V1 question type', function () {
    $normalizer = new AnswerNormalizer;
    $registry = new GraderRegistry([
        new SingleChoiceGrader, new TrueFalseGrader, new MultipleChoiceGrader,
        new ShortAnswerGrader($normalizer), new FillInBlankGrader($normalizer),
    ]);

    // If this fails, a type exists that nothing can score — the publish guard depends on it.
    foreach (QuestionType::cases() as $type) {
        expect($registry->supports($type))->toBeTrue("no grader registered for {$type->value}");
    }

    expect($registry->supportedTypes())->toHaveCount(count(QuestionType::cases()));
});
