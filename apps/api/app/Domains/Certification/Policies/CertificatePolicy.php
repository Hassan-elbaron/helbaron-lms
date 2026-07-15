<?php

namespace App\Domains\Certification\Policies;

use App\Domains\Certification\Models\Certificate;
use App\Platform\Identity\Contracts\Actor;
use App\Platform\Shared\Policies\BasePolicy;

class CertificatePolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof Actor && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function view(Actor $user, Certificate $certificate): bool
    {
        return $certificate->user_id === $user->actorId() || $user->can('certification.certificates.manage');
    }

    public function manage(Actor $user, Certificate $certificate): bool
    {
        return $user->can('certification.certificates.manage');
    }
}
