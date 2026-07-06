<?php

namespace App\Domains\Live\Enums;

enum AttendanceSource: string
{
    case SelfJoin = 'self_join';
    case ProviderImport = 'provider_import';
    case Manual = 'manual';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
