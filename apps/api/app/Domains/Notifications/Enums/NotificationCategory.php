<?php

namespace App\Domains\Notifications\Enums;

enum NotificationCategory: string
{
    case Account = 'account';
    case Learning = 'learning';
    case Commerce = 'commerce';
    case Certification = 'certification';
    case Live = 'live';
    case Crm = 'crm';
    case System = 'system';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
