<?php

namespace App\Domains\Live\Enums;

enum RecordingStatus: string
{
    case None = 'none';
    case Processing = 'processing';
    case Available = 'available';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
