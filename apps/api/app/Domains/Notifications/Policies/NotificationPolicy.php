<?php

namespace App\Domains\Notifications\Policies;

use App\Platform\Identity\Models\User;
use App\Domains\Notifications\Models\Notification;
use App\Platform\Shared\Policies\BasePolicy;

class NotificationPolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof User && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function view(User $user, Notification $notification): bool
    {
        return $notification->user_id === $user->id;
    }
}
