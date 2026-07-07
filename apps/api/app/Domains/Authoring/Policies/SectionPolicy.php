<?php

namespace App\Domains\Authoring\Policies;

use App\Domains\Authoring\Models\Section;
use App\Platform\Identity\Models\User;
use App\Platform\Shared\Policies\BasePolicy;

class SectionPolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof User && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function update(User $user, Section $section): bool
    {
        return $user->can('authoring.curriculum.manage');
    }

    public function delete(User $user, Section $section): bool
    {
        return $user->can('authoring.curriculum.manage');
    }
}
