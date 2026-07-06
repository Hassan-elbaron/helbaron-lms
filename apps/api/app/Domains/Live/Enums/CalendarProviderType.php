<?php

namespace App\Domains\Live\Enums;

enum CalendarProviderType: string
{
    case Null = 'null';
    case Google = 'google';
    case Outlook = 'outlook';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
