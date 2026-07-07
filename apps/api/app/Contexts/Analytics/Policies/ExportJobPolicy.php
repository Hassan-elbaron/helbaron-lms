<?php

namespace App\Contexts\Analytics\Policies;

use App\Contexts\Analytics\Models\ExportJob;
use App\Platform\Identity\Models\User;
use App\Platform\Shared\Policies\BasePolicy;

class ExportJobPolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof User && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function view(User $user, ExportJob $job): bool
    {
        return $job->user_id === $user->id;
    }
}
