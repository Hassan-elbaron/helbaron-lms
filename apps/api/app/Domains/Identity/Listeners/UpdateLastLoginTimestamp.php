<?php

namespace App\Domains\Identity\Listeners;

use App\Domains\Identity\Events\UserLoggedIn;

/**
 * Records the moment of a successful login on the user's most recent device row.
 */
class UpdateLastLoginTimestamp
{
    public function handle(UserLoggedIn $event): void
    {
        $event->user->devices()
            ->latest('id')
            ->limit(1)
            ->update(['last_used_at' => now()]);
    }
}
