<?php

namespace App\Domains\Assessment\Services;

use App\Domains\Assessment\Enums\QuestionType;
use Illuminate\Validation\ValidationException;

/**
 * Validates that a question's options are structurally coherent for its type.
 *
 * FormRequests validate field types; this validates MEANING — "a single-choice question with two
 * correct answers is unanswerable", "a short answer with no accepted values can never be right".
 * Catching these at authoring time is what stops an ungradable question reaching a learner.
 *
 * Note there is no `match ($type)` here. Every rule is driven by capability flags the enum
 * declares about itself (`usesOptions`, `allowsMultipleCorrect`, `isMultiPart`, `fixedOptionCount`),
 * so a new question type is validated correctly by declaring its capabilities — not by editing
 * this class.
 */
class QuestionShapeGuard
{
    /**
     * Keyed as array<int, …> rather than list<…> deliberately: the guard only iterates and counts,
     * so it accepts an option set that came straight off a relation as readily as one rebuilt from
     * a request payload.
     *
     * @param  array<int, array<string, mixed>>  $options
     *
     * @throws ValidationException
     */
    public function assertValid(QuestionType $type, array $options): void
    {
        $correct = array_values(array_filter($options, fn (array $o) => ($o['is_correct'] ?? false) === true));

        if ($type->usesOptions()) {
            $this->assertChoiceShape($type, $options, $correct);

            return;
        }

        if ($type->usesTextMatching()) {
            $this->assertTextShape($type, $correct);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     * @param  array<int, array<string, mixed>>  $correct
     */
    private function assertChoiceShape(QuestionType $type, array $options, array $correct): void
    {
        $fixed = $type->fixedOptionCount();

        if ($fixed !== null && count($options) !== $fixed) {
            $this->fail("A {$type->label()} question must have exactly {$fixed} options.");
        }

        if ($fixed === null && count($options) < 2) {
            $this->fail("A {$type->label()} question needs at least two options.");
        }

        if ($correct === []) {
            $this->fail("A {$type->label()} question needs at least one correct option.");
        }

        if (! $type->allowsMultipleCorrect() && count($correct) > 1) {
            $this->fail("A {$type->label()} question may have only one correct option.");
        }

        foreach ($options as $option) {
            if (trim((string) ($option['label'] ?? '')) === '') {
                $this->fail('Every option needs visible text.');
            }
        }
    }

    /** @param  array<int, array<string, mixed>>  $correct */
    private function assertTextShape(QuestionType $type, array $correct): void
    {
        if ($correct === []) {
            $this->fail("A {$type->label()} question needs at least one accepted answer.");
        }

        foreach ($correct as $option) {
            $value = trim((string) ($option['value'] ?? $option['label'] ?? ''));
            if ($value === '') {
                $this->fail('An accepted answer cannot be empty.');
            }
        }

        if (! $type->isMultiPart()) {
            return;
        }

        // Blanks must be numbered contiguously from zero: a gap means the learner is shown a blank
        // the answer key has no entry for, which would be silently ungradable.
        $groups = array_values(array_unique(array_map(
            fn (array $o) => (int) ($o['group_index'] ?? 0),
            $correct,
        )));
        sort($groups);

        if ($groups !== range(0, count($groups) - 1)) {
            $this->fail('Blanks must be numbered consecutively starting at 0, with an answer for each.');
        }
    }

    /** @throws ValidationException */
    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['options' => $message]);
    }
}
