<?php

namespace App\Domains\Commerce\Enums;

enum ProductType: string
{
    case Course = 'course';
    case Bundle = 'bundle';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
