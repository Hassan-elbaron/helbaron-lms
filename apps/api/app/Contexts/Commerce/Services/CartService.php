<?php

namespace App\Contexts\Commerce\Services;

use App\Contexts\Commerce\Exceptions\ProductUnavailableException;
use App\Contexts\Commerce\Models\Cart;
use App\Contexts\Commerce\Models\CartItem;
use App\Contexts\Commerce\Models\Product;
use App\Platform\Shared\Services\BaseService;

/**
 * Manages the per-user cart and computes totals (subtotal, discount, total) in minor units.
 */
class CartService extends BaseService
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly CouponService $coupons,
    ) {}

    public function currentByUserId(int $userId): Cart
    {
        return Cart::firstOrCreate(
            ['user_id' => $userId],
            ['currency' => (string) config('commerce.default_currency')],
        );
    }

    public function addProduct(Cart $cart, Product $product): CartItem
    {
        if (! $product->isActive()) {
            throw new ProductUnavailableException;
        }

        $amount = $this->pricing->effectiveMinor($product, $cart->currency);

        if ($amount === null) {
            throw new ProductUnavailableException('No price is set for this product in your currency.');
        }

        return CartItem::updateOrCreate(
            ['cart_id' => $cart->id, 'product_id' => $product->id],
            ['unit_amount_minor' => $amount],
        );
    }

    public function removeProduct(Cart $cart, Product $product): void
    {
        $cart->items()->where('product_id', $product->id)->delete();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->forceFill(['coupon_id' => null])->save();
    }

    /** @return array{subtotal_minor: int, discount_minor: int, total_minor: int} */
    public function totals(Cart $cart): array
    {
        $cart->loadMissing('items', 'coupon');

        $subtotal = (int) $cart->items->sum('unit_amount_minor');

        $discount = 0;
        if ($cart->coupon !== null) {
            $lines = $cart->items->map(fn (CartItem $i) => [
                'product_id' => $i->product_id,
                'amount_minor' => $i->unit_amount_minor,
            ]);
            $discount = $this->coupons->discountMinor($cart->coupon, $lines);
        }

        return [
            'subtotal_minor' => $subtotal,
            'discount_minor' => $discount,
            'total_minor' => max(0, $subtotal - $discount),
        ];
    }
}
