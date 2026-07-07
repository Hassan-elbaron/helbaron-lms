<?php

namespace App\Domains\Analytics\Policies;

use App\Domains\Analytics\Models\ExportJob;
use App\Domains\Identity\Models\User;
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
