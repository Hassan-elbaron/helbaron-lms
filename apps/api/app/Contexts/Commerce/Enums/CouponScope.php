<?php

namespace App\Contexts\Commerce\Enums;

enum CouponScope: string
{
    case All = 'all';
    case Products = 'products';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
