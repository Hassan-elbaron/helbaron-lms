<?php

namespace App\Platform\Shared\Helpers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

/**
 * Thin date/time helpers over Carbon. Named DateHelper to avoid shadowing PHP's DateTime.
 */
final class DateHelper
{
    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now();
    }

    public static function parse(string $value): CarbonImmutable
    {
        return CarbonImmutable::parse($value);
    }

    public static function toIso(?Carbon $date): ?string
    {
        return $date?->toIso8601String();
    }
}
