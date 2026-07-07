<?php

namespace App\Contexts\Commerce\Enums;

enum PaymentProvider: string
{
    case Fake = 'fake';
    case Stripe = 'stripe';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
