<?php

namespace App\Contexts\Commerce\Actions\Cart;

use App\Contexts\Commerce\Models\Product;
use App\Contexts\Commerce\Services\CartService;
use App\Platform\Identity\Models\User;
use App\Platform\Shared\Actions\BaseAction;

class RemoveFromCartAction extends BaseAction
{
    public function __construct(private readonly CartService $carts) {}

    public function execute(User $user, Product $product): void
    {
        $this->transaction(function () use ($user, $product): void {
            $this->carts->removeProduct($this->carts->current($user), $product);
        });
    }
}
