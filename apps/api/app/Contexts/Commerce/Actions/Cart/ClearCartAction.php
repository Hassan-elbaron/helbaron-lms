<?php

namespace App\Contexts\Commerce\Actions\Cart;

use App\Contexts\Commerce\Services\CartService;
use App\Platform\Shared\Actions\BaseAction;

class ClearCartAction extends BaseAction
{
    public function __construct(private readonly CartService $carts) {}

    public function executeByUserId(int $userId): void
    {
        $this->transaction(fn () => $this->carts->clear($this->carts->currentByUserId($userId)));
    }
}
