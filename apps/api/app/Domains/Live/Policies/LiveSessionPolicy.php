<?php

namespace App\Domains\Live\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Live\Models\LiveSession;
use App\Shared\Policies\BasePolicy;

class LiveSessionPolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof User && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function manage(User $user): bool
    {
        return $user->can('live.sessions.manage');
    }

    public function view(User $user, LiveSession $session): bool
    {
        return true;
    }
}
