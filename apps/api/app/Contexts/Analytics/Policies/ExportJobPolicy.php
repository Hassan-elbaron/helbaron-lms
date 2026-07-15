<?php

namespace App\Contexts\Analytics\Policies;

use App\Contexts\Analytics\Models\ExportJob;
use App\Platform\Identity\Contracts\Actor;
use App\Platform\Shared\Policies\BasePolicy;

class ExportJobPolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof Actor && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function view(Actor $user, ExportJob $job): bool
    {
        return $job->user_id === $user->actorId();
    }
}
