<?php

namespace App\Domains\Live\Listeners;

use App\Domains\Live\Contracts\ReminderScheduler;
use App\Domains\Live\Events\SessionCancelled;

/**
 * Cancels pending reminders when a session is cancelled.
 */
class CancelRemindersOnSessionCancelled
{
    public function __construct(private readonly ReminderScheduler $reminders) {}

    public function handle(SessionCancelled $event): void
    {
        $this->reminders->cancel($event->session);
    }
}
