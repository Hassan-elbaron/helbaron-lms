<?php

namespace App\Domains\Assessment\Enums;

/**
 * Lifecycle of a single learner attempt.
 *
 * `AwaitingReview` is intentionally present in V1 even though every V1 question type is
 * auto-gradable: it is the state an attempt lands in once a manually-graded type (Essay) exists,
 * so the grading pipeline and the API contract do not have to change to accommodate it.
 */
enum AttemptStatus: string
{
    /** Learner has started and may still save answers. */
    case InProgress = 'in_progress';
    /** Submitted; auto-grading has not finished. */
    case Submitted = 'submitted';
    /** Contains at least one answer no machine can grade. Reserved for manual-grading types. */
    case AwaitingReview = 'awaiting_review';
    /** Final score is known. */
    case Graded = 'graded';
    /** Time limit elapsed before submission. Scored on whatever was saved. */
    case Expired = 'expired';
    /** Explicitly abandoned by the learner or superseded. */
    case Abandoned = 'abandoned';

    /** Terminal states — no further answers may be written. */
    public function isFinal(): bool
    {
        return in_array($this, [self::Graded, self::Expired, self::Abandoned], true);
    }

    public function isOpen(): bool
    {
        return $this === self::InProgress;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
