<?php

namespace App\Domains\Assessment\Grading\Graders;

use App\Domains\Assessment\Enums\QuestionType;
use App\Domains\Assessment\Grading\AnswerNormalizer;
use App\Domains\Assessment\Grading\Contracts\QuestionGrader;
use App\Domains\Assessment\Grading\GradeResult;
use App\Domains\Assessment\Models\AssessmentAnswer;
use App\Domains\Assessment\Models\AssessmentQuestion;

/**
 * A prompt containing one or more blanks. Each blank is identified by `group_index` on the
 * question's options; every option in a group is an accepted answer for that blank.
 *
 * Learner payload: {"blanks": {"0": "carbon", "1": "dioxide"}}
 *
 * Default is all-or-nothing across blanks. `config: {"partial_credit": true}` scores the fraction
 * of blanks filled correctly, which is the fairer default for multi-blank cloze questions — but it
 * stays opt-in so existing scoring never changes silently.
 */
class FillInBlankGrader implements QuestionGrader
{
    public function __construct(private readonly AnswerNormalizer $normalizer) {}

    public function type(): QuestionType
    {
        return QuestionType::FillInBlank;
    }

    public function grade(AssessmentQuestion $question, AssessmentAnswer $answer): GradeResult
    {
        // Group accepted values by blank index. Groups come from the answer key, never from the
        // learner payload — otherwise an omitted blank would shrink the denominator and inflate
        // the score.
        /** @var array<int, list<string>> $acceptedByBlank keyed by blank index, matching AssessmentAnswer::blanks() */
        $acceptedByBlank = [];
        foreach ($question->options->where('is_correct', true) as $option) {
            $value = $option->comparableValue();
            if ($value !== '') {
                $acceptedByBlank[$option->group_index][] = $value;
            }
        }

        if ($acceptedByBlank === []) {
            return GradeResult::incorrect();
        }

        $caseSensitive = $question->setting('case_sensitive', false) === true;
        $normalizeArabic = $question->setting('normalize_arabic', true) !== false;
        $submitted = $answer->blanks();

        $hits = 0;
        foreach ($acceptedByBlank as $index => $accepted) {
            $value = $submitted[$index] ?? '';
            if ($value !== '' && $this->normalizer->matchesAny($value, $accepted, $caseSensitive, $normalizeArabic)) {
                $hits++;
            }
        }

        $total = count($acceptedByBlank);

        if ($question->setting('partial_credit', false) === true) {
            return GradeResult::partial($hits / $total);
        }

        return $hits === $total ? GradeResult::correct() : GradeResult::incorrect();
    }
}
