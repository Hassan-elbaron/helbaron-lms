<?php

namespace App\Domains\Assessment\Enums;

/**
 * Author-declared question difficulty. Kept as an enum (not a free integer) so question banks can
 * later assemble balanced attempts — "5 easy, 3 medium, 2 hard" — without a data cleanup pass.
 */
enum Difficulty: string
{
    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $d) => $d->value, self::cases());
    }
}
