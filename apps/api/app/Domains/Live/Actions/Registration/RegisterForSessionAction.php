<?php

namespace App\Domains\Live\Actions\Registration;

use App\Domains\Live\Enums\RegistrationStatus;
use App\Domains\Live\Events\UserRegisteredForSession;
use App\Domains\Live\Models\LiveSession;
use App\Domains\Live\Models\SessionRegistration;
use App\Domains\Live\Services\RegistrationValidationService;
use App\Platform\Shared\Actions\BaseAction;

/**
 * Registers a user for a session under a capacity lock: within capacity => registered, otherwise
 * => waitlisted. Idempotent per (session, user).
 */
class RegisterForSessionAction extends BaseAction
{
    public function __construct(private readonly RegistrationValidationService $validator) {}

    public function executeByUserId(LiveSession $session, int $userId): SessionRegistration
    {
        $this->validator->assertOpen($session);

        [$registration, $created] = $this->transaction(function () use ($session, $userId): array {
            // Lock the session row to serialize capacity decisions.
            $locked = LiveSession::whereKey($session->id)->lockForUpdate()->first();

            $existing = SessionRegistration::where('session_id', $locked->id)->where('user_id', $userId)->first();
            if ($existing !== null) {
                if ($existing->status === RegistrationStatus::Cancelled) {
                    $existing->forceFill([
                        'status' => $this->validator->statusForNewRegistration($locked),
                        'registered_at' => now(),
                    ])->save();
                }

                return [$existing, false];
            }

            $registration = SessionRegistration::create([
                'session_id' => $locked->id,
                'user_id' => $userId,
                'status' => $this->validator->statusForNewRegistration($locked),
                'registered_at' => now(),
            ]);

            return [$registration, true];
        });

        if ($created) {
            UserRegisteredForSession::dispatch($session);
        }

        return $registration;
    }
}
