<?php

namespace App\Domains\Live\Services;

use App\Domains\Live\Enums\RegistrationStatus;
use App\Domains\Live\Exceptions\NotRegisteredException;
use App\Domains\Live\Models\LiveSession;
use App\Platform\Shared\Services\BaseService;

/**
 * Validates that attendance can be recorded: the user must hold an active registration.
 */
class AttendanceValidationService extends BaseService
{
    public function assertCanAttendByUserId(LiveSession $session, int $userId): void
    {
        $registered = $session->registrations()
            ->where('user_id', $userId)
            ->whereIn('status', [RegistrationStatus::Registered->value, RegistrationStatus::Waitlisted->value])
            ->exists();

        if (! $registered) {
            throw new NotRegisteredException;
        }
    }
}
