<?php

namespace App\Contexts\Commerce\Actions\Cart;

use App\Contexts\Commerce\Models\Cart;
use App\Contexts\Commerce\Services\CartService;
use App\Contexts\Commerce\Services\CouponService;
use App\Platform\Shared\Actions\BaseAction;

class ApplyCouponAction extends BaseAction
{
    public function __construct(
        private readonly CartService $carts,
        private readonly CouponService $coupons,
    ) {}

    public function executeByUserId(int $userId, string $code): Cart
    {
        return $this->transaction(function () use ($userId, $code): Cart {
            $coupon = $this->coupons->findValid($code); // throws on invalid/expired/exhausted
            $cart = $this->carts->currentByUserId($userId);
            $cart->forceFill(['coupon_id' => $coupon->id])->save();

            return $cart->fresh(['items', 'coupon']);
        });
    }
}
