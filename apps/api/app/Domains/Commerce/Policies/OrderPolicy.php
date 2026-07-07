<?php

namespace App\Domains\Commerce\Policies;

use App\Domains\Commerce\Models\Order;
use App\Platform\Identity\Models\User;
use App\Platform\Shared\Policies\BasePolicy;

class OrderPolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof User && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function view(User $user, Order $order): bool
    {
        return $order->user_id === $user->id || $user->can('commerce.orders.view');
    }

    public function refund(User $user, Order $order): bool
    {
        return $user->can('commerce.orders.view'); // management gate
    }
}
