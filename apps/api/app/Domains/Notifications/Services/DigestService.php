<?php

namespace App\Domains\Notifications\Services;

use App\Domains\Identity\Models\User;
use App\Domains\Notifications\Models\Notification;
use App\Platform\Shared\Services\BaseService;
use Illuminate\Support\Collection;

/**
 * Builds a digest summary (metadata) of a user's recent unread notifications. Delivery of the
 * digest itself goes through the dispatcher on a schedule (future scheduler wiring).
 */
class DigestService extends BaseService
{
    public function pendingFor(User $user, int $sinceHours = 24): Collection
    {
        return Notification::where('user_id', $user->id)
            ->unread()
            ->where('created_at', '>=', now()->subHours($sinceHours))
            ->latest('id')
            ->get();
    }
}
