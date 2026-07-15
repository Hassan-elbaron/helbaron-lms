<?php

namespace App\Platform\Notifications\Services;

use App\Platform\Notifications\Models\Notification;
use App\Platform\Shared\Services\BaseService;
use Illuminate\Support\Collection;

/**
 * Builds a digest summary (metadata) of a user's recent unread notifications. Delivery of the
 * digest itself goes through the dispatcher on a schedule (future scheduler wiring).
 */
class DigestService extends BaseService
{
    public function pendingForUserId(int $userId, int $sinceHours = 24): Collection
    {
        return Notification::where('user_id', $userId)
            ->unread()
            ->where('created_at', '>=', now()->subHours($sinceHours))
            ->latest('id')
            ->get();
    }
}
