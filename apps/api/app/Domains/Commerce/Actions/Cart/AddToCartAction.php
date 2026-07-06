<?php

namespace App\Domains\Commerce\Actions\Cart;

use App\Domains\Commerce\Models\Cart;
use App\Domains\Commerce\Models\Product;
use App\Domains\Commerce\Services\CartService;
use App\Domains\Identity\Models\User;
use App\Shared\Actions\BaseAction;

class AddToCartAction extends BaseAction
{
    public function __construct(private readonly CartService $carts) {}

    public function execute(User $user, Product $product): Cart
    {
        return $this->transaction(function () use ($user, $product): Cart {
            $cart = $this->carts->current($user);
            $this->carts->addProduct($cart, $product);

            return $cart->fresh(['items', 'coupon']);
        });
    }
}
