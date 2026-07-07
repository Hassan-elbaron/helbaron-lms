<?php

namespace App\Contexts\Analytics\Enums;

enum ReportVisibility: string
{
    case Private = 'private';
    case Shared = 'shared';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
