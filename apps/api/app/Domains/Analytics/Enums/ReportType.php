<?php

namespace App\Domains\Analytics\Enums;

enum ReportType: string
{
    case Metric = 'metric';
    case Funnel = 'funnel';
    case Cohort = 'cohort';
    case Table = 'table';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
