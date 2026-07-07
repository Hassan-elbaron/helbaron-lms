<?php

namespace App\Domains\Commerce\Actions\Cart;

use App\Domains\Commerce\Models\Product;
use App\Domains\Commerce\Services\CartService;
use App\Domains\Identity\Models\User;
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
