<?php

namespace App\Contexts\Analytics\Enums;

enum MetricUnit: string
{
    case Count = 'count';
    case CurrencyMinor = 'currency_minor';
    case Percentage = 'percentage';
    case Duration = 'duration';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
