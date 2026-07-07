<?php

namespace App\Domains\Crm\Policies;

use App\Domains\Crm\Models\ConsultingRequest;
use App\Domains\Identity\Models\User;
use App\Platform\Shared\Policies\BasePolicy;

class ConsultingRequestPolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof User && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function view(User $user, ConsultingRequest $request): bool
    {
        return $request->requested_by === $user->id || $user->can('crm.consulting.manage');
    }
}
