<?php

namespace App\Platform\Notifications\Services;

use App\Platform\Notifications\Models\Notification;
use App\Platform\Shared\Services\BaseService;
use Illuminate\Support\Collection;

/**
 * Builds a digest summary (metadata) of a user's recent unread notifications.
 *
 * NOT WIRED. Nothing in the application calls this — there is no scheduled command, no delivery
 * path, and no dedup ledger. The comment that used to sit here said delivery "goes through the
 * dispatcher on a schedule (future scheduler wiring)", which reads as a description of something
 * that exists; it does not. Saying so plainly is the point: the user-facing setting has been
 * withdrawn until it does (see config/notifications.php -> notifications.digest).
 *
 * Kept rather than deleted because it is a correct and useful head start on the query side. It is
 * the DELIVERY half that is missing, and that is the half with the hard problems in it.
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
