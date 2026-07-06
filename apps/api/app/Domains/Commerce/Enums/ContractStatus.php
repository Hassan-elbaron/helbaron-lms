<?php

namespace App\Domains\Commerce\Enums;

enum ContractStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
