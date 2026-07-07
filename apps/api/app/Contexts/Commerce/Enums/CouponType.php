<?php

namespace App\Contexts\Commerce\Enums;

enum CouponType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
