<?php

namespace App\Platform\Notifications\Policies;

use App\Platform\Identity\Contracts\Actor;
use App\Platform\Notifications\Models\Notification;
use App\Platform\Shared\Policies\BasePolicy;

class NotificationPolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof Actor && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function view(Actor $user, Notification $notification): bool
    {
        return $notification->user_id === $user->actorId();
    }
}
