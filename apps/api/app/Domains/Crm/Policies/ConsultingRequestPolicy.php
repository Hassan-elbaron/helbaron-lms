<?php

namespace App\Domains\Crm\Policies;

use App\Domains\Crm\Models\ConsultingRequest;
use App\Platform\Identity\Contracts\Actor;
use App\Platform\Shared\Policies\BasePolicy;

class ConsultingRequestPolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof Actor && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function view(Actor $user, ConsultingRequest $request): bool
    {
        return $request->requested_by === $user->actorId() || $user->can('crm.consulting.manage');
    }
}
