<?php

namespace App\Domains\Learning\Enums;

/**
 * How an enrollment was granted. Commerce will use Purchase later; Learning stays payment-free.
 */
enum EnrollmentSource: string
{
    case Free = 'free';
    case Purchase = 'purchase';
    case Manual = 'manual';
    case Grant = 'grant';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
