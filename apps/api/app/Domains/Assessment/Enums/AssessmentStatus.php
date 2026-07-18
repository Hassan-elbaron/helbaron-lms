<?php

namespace App\Domains\Assessment\Enums;

/**
 * Publish lifecycle of an Assessment. Deliberately NOT Authoring's PublishState: Assessment is an
 * independent domain and must not depend on Authoring (see the Deptrac layer rules). It adds
 * `Archived`, which curriculum publish states have no use for — a retired exam must stay readable
 * for historical attempts while being unattachable to new lessons.
 */
enum AssessmentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function isPublished(): bool
    {
        return $this === self::Published;
    }

    /** Only published assessments may be attempted by a learner. */
    public function isAttemptable(): bool
    {
        return $this === self::Published;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
