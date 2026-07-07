<?php

namespace App\Domains\Live\Services;

use App\Domains\Live\Enums\LiveSessionStatus;
use App\Domains\Live\Exceptions\SessionCancelledException;
use App\Domains\Live\Exceptions\SessionNotOpenException;
use App\Domains\Live\Models\LiveSession;
use App\Platform\Shared\Services\BaseService;

/**
 * Validates whether a session accepts registrations and decides registered vs waitlisted based
 * on capacity. Capacity is checked under a lock in the action.
 */
class RegistrationValidationService extends BaseService
{
    public function assertOpen(LiveSession $session): void
    {
        if ($session->status === LiveSessionStatus::Cancelled) {
            throw new SessionCancelledException;
        }

        if (! in_array($session->status, [LiveSessionStatus::Scheduled, LiveSessionStatus::Live], true)) {
            throw new SessionNotOpenException;
        }

        if ($session->ends_at->isPast()) {
            throw new SessionNotOpenException('This session has already ended.');
        }
    }

    /** Returns 'registered' if capacity allows, otherwise 'waitlisted'. */
    public function statusForNewRegistration(LiveSession $session): string
    {
        return $session->isFull() ? 'waitlisted' : 'registered';
    }
}
