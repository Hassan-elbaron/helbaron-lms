<?php

namespace App\Domains\Commerce\Actions\Cart;

use App\Domains\Commerce\Services\CartService;
use App\Domains\Identity\Models\User;
use App\Shared\Actions\BaseAction;

class ClearCartAction extends BaseAction
{
    public function __construct(private readonly CartService $carts) {}

    public function execute(User $user): void
    {
        $this->transaction(fn () => $this->carts->clear($this->carts->current($user)));
    }
}
