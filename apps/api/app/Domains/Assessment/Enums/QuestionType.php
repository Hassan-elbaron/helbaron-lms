<?php

namespace App\Domains\Assessment\Enums;

/**
 * Question type discriminator.
 *
 * This is the ONLY place a type is declared. Everything type-specific — validation, grading,
 * option semantics — is resolved from this value through a registry (see
 * `App\Domains\Assessment\Grading\GraderRegistry`), never through `match`/`if` chains in
 * actions or controllers. Adding Essay, Matching, Ordering, Hotspot, Numeric, Code, DragDrop or
 * an AI-evaluated type is therefore: add a case here + register a grader + add a validator.
 * No migration, no schema change, no edits to existing graders.
 *
 * Per-type settings live in `assessment_questions.config` (json) and accepted/choice answers in
 * `assessment_question_options`, so new types need no new columns.
 */
enum QuestionType: string
{
    // ── V1: implemented ────────────────────────────────────────────────────
    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case TrueFalse = 'true_false';
    case ShortAnswer = 'short_answer';
    case FillInBlank = 'fill_in_blank';

    public function label(): string
    {
        return match ($this) {
            self::SingleChoice => 'Single choice',
            self::MultipleChoice => 'Multiple choice',
            self::TrueFalse => 'True / False',
            self::ShortAnswer => 'Short answer',
            self::FillInBlank => 'Fill in the blank',
        };
    }

    /**
     * Types whose correctness is decided by selecting from `assessment_question_options`
     * rows (as opposed to free text matched against accepted values).
     */
    public function usesOptions(): bool
    {
        return in_array($this, [self::SingleChoice, self::MultipleChoice, self::TrueFalse], true);
    }

    /**
     * Types where the learner submits text that is compared against the accepted values stored
     * on the question's options. `fill_in_blank` submits one value per blank.
     */
    public function usesTextMatching(): bool
    {
        return in_array($this, [self::ShortAnswer, self::FillInBlank], true);
    }

    /**
     * Whether more than one option may be flagged correct. False for single_choice/true_false,
     * where a second correct option makes the question unanswerable.
     */
    public function allowsMultipleCorrect(): bool
    {
        return $this !== self::SingleChoice && $this !== self::TrueFalse;
    }

    /**
     * Whether the question has several independently-graded parts addressed by `group_index`
     * (blanks today; matching pairs later).
     */
    public function isMultiPart(): bool
    {
        return $this === self::FillInBlank;
    }

    /** Fixed option count, when the type mandates one. true_false is always exactly two. */
    public function fixedOptionCount(): ?int
    {
        return $this === self::TrueFalse ? 2 : null;
    }

    /**
     * Whether an attempt can be finalised without a human. Every V1 type is auto-gradable;
     * this flag exists so manual types (Essay) can be added later and drive the
     * `AttemptStatus::AwaitingReview` path without changing any existing code.
     */
    public function isAutoGradable(): bool
    {
        return true;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}
