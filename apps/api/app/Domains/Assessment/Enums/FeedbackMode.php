<?php

namespace App\Domains\Assessment\Enums;

/**
 * When a learner is allowed to see per-question correctness, explanations and option feedback.
 * Enforced server-side when serialising an attempt — the API must never ship the answer key to a
 * client that is not entitled to it yet.
 */
enum FeedbackMode: string
{
    /** Reveal correctness and explanations as each answer is saved (practice mode). */
    case Immediate = 'immediate';
    /** Reveal once the attempt is submitted and graded. The default. */
    case AfterSubmit = 'after_submit';
    /** Never reveal the answer key; the learner sees only the final score. */
    case Never = 'never';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $m) => $m->value, self::cases());
    }
}
