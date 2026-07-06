<?php

namespace App\Domains\Notifications\Enums;

enum DigestFrequency: string
{
    case None = 'none';
    case Daily = 'daily';
    case Weekly = 'weekly';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
