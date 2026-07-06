<?php

namespace App\Domains\Notifications\Enums;

enum ProviderType: string
{
    case Fake = 'fake';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
