<?php

namespace App\Contexts\Commerce\Enums;

enum TransactionType: string
{
    case Charge = 'charge';
    case Refund = 'refund';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
