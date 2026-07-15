<?php

namespace App\Contexts\Commerce\Actions\Cart;

use App\Contexts\Commerce\Models\Cart;
use App\Contexts\Commerce\Models\Product;
use App\Contexts\Commerce\Services\CartService;
use App\Platform\Shared\Actions\BaseAction;

class AddToCartAction extends BaseAction
{
    public function __construct(private readonly CartService $carts) {}

    public function executeByUserId(int $userId, Product $product): Cart
    {
        return $this->transaction(function () use ($userId, $product): Cart {
            $cart = $this->carts->currentByUserId($userId);
            $this->carts->addProduct($cart, $product);

            return $cart->fresh(['items', 'coupon']);
        });
    }
}
