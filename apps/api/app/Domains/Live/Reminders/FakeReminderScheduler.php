<?php

namespace App\Domains\Live\Reminders;

use App\Domains\Live\Contracts\ReminderScheduler;
use App\Domains\Live\Enums\ReminderStatus;
use App\Domains\Live\Models\LiveSession;
use App\Domains\Live\Models\SessionReminder;

/**
 * Records reminder rows for a session (no delivery here — the Notifications domain will send).
 */
class FakeReminderScheduler implements ReminderScheduler
{
    public function schedule(LiveSession $session, array $offsetsMinutes): void
    {
        foreach ($offsetsMinutes as $offset) {
            SessionReminder::updateOrCreate(
                ['session_id' => $session->id, 'offset_minutes' => $offset],
                [
                    'channel' => 'email',
                    'scheduled_at' => $session->starts_at->copy()->subMinutes($offset),
                    'status' => ReminderStatus::Pending->value,
                ],
            );
        }
    }

    public function cancel(LiveSession $session): void
    {
        $session->reminders()->where('status', ReminderStatus::Pending->value)
            ->update(['status' => ReminderStatus::Cancelled->value]);
    }
}
