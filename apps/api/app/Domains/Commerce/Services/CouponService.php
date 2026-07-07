<?php

namespace App\Domains\Commerce\Services;

use App\Domains\Commerce\Enums\CouponScope;
use App\Domains\Commerce\Enums\CouponType;
use App\Domains\Commerce\Exceptions\CouponExhaustedException;
use App\Domains\Commerce\Exceptions\CouponExpiredException;
use App\Domains\Commerce\Exceptions\CouponInvalidException;
use App\Domains\Commerce\Models\Coupon;
use App\Platform\Shared\Services\BaseService;
use Illuminate\Support\Collection;

/**
 * Validates coupons and computes discounts. No redemption side effects here — redemption is
 * recorded under a lock during checkout.
 */
class CouponService extends BaseService
{
    public function findValid(string $code): Coupon
    {
        $coupon = Coupon::where('code', $code)->first();

        if ($coupon === null || ! $coupon->is_active) {
            throw new CouponInvalidException;
        }

        if (! $coupon->isWithinWindow()) {
            throw new CouponExpiredException;
        }

        if ($coupon->isExhausted()) {
            throw new CouponExhaustedException;
        }

        return $coupon;
    }

    /**
     * Discount (minor units) for a set of line items [{product_id, amount_minor}].
     *
     * @param  Collection<int, array{product_id: int, amount_minor: int}>  $lines
     */
    public function discountMinor(Coupon $coupon, Collection $lines): int
    {
        $eligible = $lines;

        if ($coupon->scope === CouponScope::Products) {
            $ids = $coupon->products()->pluck('products.id')->flip();
            $eligible = $lines->filter(fn ($l) => $ids->has($l['product_id']));
        }

        $base = (int) $eligible->sum('amount_minor');

        if ($base <= 0) {
            return 0;
        }

        return match ($coupon->type) {
            CouponType::Percentage => (int) floor($base * min(100, $coupon->value) / 100),
            CouponType::Fixed => min($coupon->value, $base),
        };
    }
}
