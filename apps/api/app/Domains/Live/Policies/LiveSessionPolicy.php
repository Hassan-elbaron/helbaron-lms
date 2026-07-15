<?php

namespace App\Domains\Live\Policies;

use App\Domains\Live\Models\LiveSession;
use App\Platform\Identity\Contracts\Actor;
use App\Platform\Shared\Policies\BasePolicy;

class LiveSessionPolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof Actor && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function manage(Actor $user): bool
    {
        return $user->can('live.sessions.manage');
    }

    public function view(Actor $user, LiveSession $session): bool
    {
        return true;
    }
}
