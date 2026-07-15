<?php

namespace App\Domains\Live\Actions\Registration;

use App\Domains\Live\Enums\RegistrationStatus;
use App\Domains\Live\Exceptions\JoinWindowClosedException;
use App\Domains\Live\Exceptions\NotRegisteredException;
use App\Domains\Live\Exceptions\SessionCancelledException;
use App\Domains\Live\Models\LiveSession;
use App\Domains\Live\Services\JoinTokenService;
use App\Platform\Shared\Actions\BaseAction;

/**
 * Issues a single-use join token + join URL when the window is open, or returns a waiting-room
 * state otherwise. The raw meeting URL is only revealed together with a fresh token.
 */
class JoinSessionAction extends BaseAction
{
    public function __construct(private readonly JoinTokenService $tokens) {}

    /** @return array{state: string, join_url?: string, token?: string, expires_at?: string, starts_at?: string} */
    public function executeByUserId(LiveSession $session, int $userId): array
    {
        if ($session->isCancelled()) {
            throw new SessionCancelledException;
        }

        $registered = $session->registrations()->where('user_id', $userId)
            ->where('status', RegistrationStatus::Registered->value)->exists();

        if (! $registered) {
            throw new NotRegisteredException;
        }

        if (! $this->tokens->windowOpen($session)) {
            if ($session->waiting_room) {
                return ['state' => 'waiting_room', 'starts_at' => $session->starts_at->toIso8601String()];
            }

            throw new JoinWindowClosedException;
        }

        $token = $this->transaction(fn () => $this->tokens->issueByUserId($session, $userId));

        return [
            'state' => 'ready',
            'join_url' => $session->join_url,
            'token' => $token->token,
            'expires_at' => $token->expires_at->toIso8601String(),
        ];
    }
}
