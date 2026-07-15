<?php

namespace App\Contexts\Commerce\Policies;

use App\Contexts\Commerce\Models\Order;
use App\Platform\Identity\Contracts\Actor;
use App\Platform\Shared\Policies\BasePolicy;

class OrderPolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof Actor && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function view(Actor $user, Order $order): bool
    {
        return $order->user_id === $user->actorId() || $user->can('commerce.orders.view');
    }

    public function refund(Actor $user, Order $order): bool
    {
        return $user->can('commerce.orders.view'); // management gate
    }
}
