<?php

namespace App\Domains\Commerce\Enums;

enum CommercePermission: string
{
    case ManageProducts = 'commerce.products.manage';
    case ManageCoupons = 'commerce.coupons.manage';
    case ViewOrders = 'commerce.orders.view';
    case ManageContracts = 'commerce.contracts.manage';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
