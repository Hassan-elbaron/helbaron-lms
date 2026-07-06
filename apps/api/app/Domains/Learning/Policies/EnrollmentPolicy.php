<?php

namespace App\Domains\Learning\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Learning\Models\Enrollment;
use App\Shared\Policies\BasePolicy;

/**
 * A learner may only view/act on their own enrollments. super_admin bypasses via before().
 */
class EnrollmentPolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof User && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        return $enrollment->user_id === $user->id;
    }
}
