<?php

namespace App\Domains\Live\Contracts;

use App\Domains\Live\Models\LiveSession;

/**
 * Reminder scheduling abstraction. The Fake implementation records reminder rows; a real
 * scheduler would enqueue delivery jobs (handled by the Notifications domain later).
 */
interface ReminderScheduler
{
    /** @param array<int, int> $offsetsMinutes minutes-before-start for each reminder */
    public function schedule(LiveSession $session, array $offsetsMinutes): void;

    public function cancel(LiveSession $session): void;
}
