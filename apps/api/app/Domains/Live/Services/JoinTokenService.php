<?php

namespace App\Domains\Live\Services;

use App\Domains\Live\Models\LiveSession;
use App\Domains\Live\Models\SessionJoinToken;
use App\Platform\Shared\Services\BaseService;
use Illuminate\Support\Str;

/**
 * Issues single-use, expiring join tokens. The raw meeting URL is only ever surfaced together
 * with a freshly minted token — never via the session resource.
 */
class JoinTokenService extends BaseService
{
    public function issueByUserId(LiveSession $session, int $userId): SessionJoinToken
    {
        return SessionJoinToken::create([
            'session_id' => $session->id,
            'user_id' => $userId,
            'token' => Str::random(48),
            'expires_at' => now()->addMinutes((int) config('live.join.token_ttl_minutes', 120)),
        ]);
    }

    /** Is the join window open (waiting room)? */
    public function windowOpen(LiveSession $session): bool
    {
        if ($session->status->value === 'live') {
            return true;
        }

        $opensAt = $session->starts_at->copy()->subMinutes((int) config('live.join.window_minutes_before', 15));

        return now()->betweenIncluded($opensAt, $session->ends_at);
    }
}
