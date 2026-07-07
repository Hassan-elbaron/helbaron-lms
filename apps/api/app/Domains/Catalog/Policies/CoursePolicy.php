<?php

namespace App\Domains\Catalog\Policies;

use App\Domains\Catalog\Models\Course;
use App\Domains\Identity\Models\User;
use App\Platform\Shared\Policies\BasePolicy;

/**
 * Course authorization. Reading published courses is public (no policy needed); mutations
 * require catalog management permissions. super_admin bypasses via before().
 */
class CoursePolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof User && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function create(User $user): bool
    {
        return $user->can('catalog.courses.manage');
    }

    public function update(User $user, Course $course): bool
    {
        return $user->can('catalog.courses.manage');
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->can('catalog.courses.manage');
    }
}
