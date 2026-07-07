<?php

namespace App\Domains\Notifications\Services;

use App\Platform\Shared\Services\BaseService;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Per-user send throttle. Returns false when the limit is exceeded (caller retries later).
 */
class RateLimiterService extends BaseService
{
    public function allow(int|string $userId): bool
    {
        $max = (int) config('notifications.rate_limit.per_minute', 30);

        $executed = RateLimiter::attempt("notifications:{$userId}", $max, fn () => true, 60);

        return $executed !== false;
    }
}
