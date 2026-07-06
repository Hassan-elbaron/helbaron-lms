<?php

namespace App\Domains\Crm\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case Working = 'working';
    case Qualified = 'qualified';
    case Converted = 'converted';
    case Lost = 'lost';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
