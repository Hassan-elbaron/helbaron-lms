<?php

namespace App\Domains\Live\Enums;

enum LivePermission: string
{
    case ManageLive = 'live.sessions.manage';
    case ViewLive = 'live.sessions.view';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
