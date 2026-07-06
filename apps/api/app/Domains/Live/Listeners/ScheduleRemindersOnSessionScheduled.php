<?php

namespace App\Domains\Live\Listeners;

use App\Domains\Live\Contracts\ReminderScheduler;
use App\Domains\Live\Events\SessionScheduled;

/**
 * Schedules reminder rows when a session is scheduled (via the ReminderScheduler abstraction).
 */
class ScheduleRemindersOnSessionScheduled
{
    public function __construct(private readonly ReminderScheduler $reminders) {}

    public function handle(SessionScheduled $event): void
    {
        $this->reminders->schedule($event->session, (array) config('live.reminder.offsets_minutes', [60]));
    }
}
