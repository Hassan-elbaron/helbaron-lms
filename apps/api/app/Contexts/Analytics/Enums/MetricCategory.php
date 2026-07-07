<?php

namespace App\Contexts\Analytics\Enums;

enum MetricCategory: string
{
    case General = 'general';
    case Enrollment = 'enrollment';
    case Completion = 'completion';
    case Revenue = 'revenue';
    case Commerce = 'commerce';
    case Certification = 'certification';
    case Live = 'live';
    case Crm = 'crm';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
