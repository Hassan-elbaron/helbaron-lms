<?php

namespace App\Domains\Authoring\Policies;

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Identity\Models\User;
use App\Shared\Policies\BasePolicy;

class LessonPolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof User && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $user->can('authoring.curriculum.manage');
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return $user->can('authoring.curriculum.manage');
    }
}
