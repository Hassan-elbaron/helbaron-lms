<?php

namespace App\Domains\Crm\Enums;

enum OrganizationStatus: string
{
    case Prospect = 'prospect';
    case Active = 'active';
    case Churned = 'churned';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
