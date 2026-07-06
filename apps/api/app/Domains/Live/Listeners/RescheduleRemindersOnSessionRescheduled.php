<?php

namespace App\Domains\Live\Listeners;

use App\Domains\Live\Contracts\ReminderScheduler;
use App\Domains\Live\Events\SessionRescheduled;

/**
 * Re-computes reminder rows when a session is rescheduled.
 */
class RescheduleRemindersOnSessionRescheduled
{
    public function __construct(private readonly ReminderScheduler $reminders) {}

    public function handle(SessionRescheduled $event): void
    {
        $this->reminders->schedule($event->session, (array) config('live.reminder.offsets_minutes', [60]));
    }
}
