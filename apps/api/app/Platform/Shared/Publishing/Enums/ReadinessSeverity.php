<?php

namespace App\Platform\Shared\Publishing\Enums;

/**
 * How badly a readiness finding matters.
 *
 * Only Blocker prevents publishing. Warning exists so the panel can tell an author their course is
 * thin — no description, no thumbnail — without inventing a rule that stops them shipping. Anything
 * that would stop a publish MUST be a Blocker, because the guard derives its verdict from exactly
 * this field; a finding is never "sort of" blocking.
 *
 * Promoting a rule from Warning to Blocker retroactively blocks content that publishes today. That
 * is a breaking change to an author's workflow and needs to be treated as one.
 */
enum ReadinessSeverity: string
{
    case Blocker = 'blocker';
    case Warning = 'warning';

    public function blocksPublishing(): bool
    {
        return $this === self::Blocker;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
