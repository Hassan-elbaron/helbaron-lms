<?php

namespace App\Domains\Certification\Policies;

use App\Domains\Certification\Models\Certificate;
use App\Platform\Identity\Models\User;
use App\Platform\Shared\Policies\BasePolicy;

class CertificatePolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof User && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function view(User $user, Certificate $certificate): bool
    {
        return $certificate->user_id === $user->id || $user->can('certification.certificates.manage');
    }

    public function manage(User $user, Certificate $certificate): bool
    {
        return $user->can('certification.certificates.manage');
    }
}
