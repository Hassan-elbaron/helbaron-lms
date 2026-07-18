<?php

namespace App\Domains\Assessment\Grading;

use App\Domains\Assessment\Enums\QuestionType;
use App\Domains\Assessment\Exceptions\UnsupportedQuestionTypeException;
use App\Domains\Assessment\Grading\Contracts\QuestionGrader;

/**
 * Resolves the grader for a question type. Registered in AssessmentServiceProvider as a singleton.
 *
 * Registering a new type is the ONLY change needed to support it — this class has no knowledge of
 * any concrete type, and neither does anything downstream of it.
 */
class GraderRegistry
{
    /** @var array<string, QuestionGrader> keyed by QuestionType value */
    private array $graders = [];

    /** @param  iterable<QuestionGrader>  $graders */
    public function __construct(iterable $graders = [])
    {
        foreach ($graders as $grader) {
            $this->register($grader);
        }
    }

    public function register(QuestionGrader $grader): self
    {
        $this->graders[$grader->type()->value] = $grader;

        return $this;
    }

    /** @throws UnsupportedQuestionTypeException when no grader is registered for the type */
    public function for(QuestionType $type): QuestionGrader
    {
        return $this->graders[$type->value]
            ?? throw new UnsupportedQuestionTypeException($type);
    }

    public function supports(QuestionType $type): bool
    {
        return isset($this->graders[$type->value]);
    }

    /**
     * Types this build can actually grade. Used by the publish guard so an assessment can never be
     * published containing a question nothing can score.
     *
     * @return list<QuestionType>
     */
    public function supportedTypes(): array
    {
        return array_values(array_filter(
            QuestionType::cases(),
            fn (QuestionType $type) => $this->supports($type),
        ));
    }
}
