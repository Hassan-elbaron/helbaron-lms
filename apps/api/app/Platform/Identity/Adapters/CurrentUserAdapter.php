<?php

namespace App\Platform\Identity\Adapters;

use App\Platform\Identity\Contracts\CurrentUserPort;
use App\Platform\Identity\Contracts\Data\UserRef;
use App\Platform\Identity\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Reads the already-authenticated principal from the framework auth guard. Performs no
 * authentication of its own. Lives inside Identity (the only layer allowed to touch the User model).
 */
final class CurrentUserAdapter implements CurrentUserPort
{
    public function currentUserId(): ?int
    {
        $id = Auth::id();

        return $id === null ? null : (int) $id;
    }

    public function currentUserRef(): ?UserRef
    {
        $user = Auth::user();

        return $user instanceof User ? $user->toUserRef() : null;
    }

    public function isAuthenticated(): bool
    {
        return Auth::check();
    }
}
