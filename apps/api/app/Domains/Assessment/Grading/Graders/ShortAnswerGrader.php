<?php

namespace App\Domains\Assessment\Grading\Graders;

use App\Domains\Assessment\Enums\QuestionType;
use App\Domains\Assessment\Grading\AnswerNormalizer;
use App\Domains\Assessment\Grading\Contracts\QuestionGrader;
use App\Domains\Assessment\Grading\GradeResult;
use App\Domains\Assessment\Models\AssessmentAnswer;
use App\Domains\Assessment\Models\AssessmentQuestion;

/**
 * Free text compared against the author's list of accepted answers (options flagged `is_correct`,
 * `group_index` 0). Matching is exact AFTER normalisation — see AnswerNormalizer for what that
 * forgives (case, whitespace, Arabic orthography, Arabic-Indic digits, smart quotes).
 *
 * Per-question overrides via `config`: {"case_sensitive": true, "normalize_arabic": false}.
 */
class ShortAnswerGrader implements QuestionGrader
{
    public function __construct(private readonly AnswerNormalizer $normalizer) {}

    public function type(): QuestionType
    {
        return QuestionType::ShortAnswer;
    }

    public function grade(AssessmentQuestion $question, AssessmentAnswer $answer): GradeResult
    {
        $submitted = $answer->text();

        if (trim($submitted) === '') {
            return GradeResult::incorrect();
        }

        $accepted = $question->options
            ->where('is_correct', true)
            ->map(fn ($option) => $option->comparableValue())
            ->filter(fn (string $value) => $value !== '')
            ->all();

        if ($accepted === []) {
            return GradeResult::incorrect();
        }

        return $this->normalizer->matchesAny(
            $submitted,
            $accepted,
            $question->setting('case_sensitive', false) === true,
            $question->setting('normalize_arabic', true) !== false,
        ) ? GradeResult::correct() : GradeResult::incorrect();
    }
}
