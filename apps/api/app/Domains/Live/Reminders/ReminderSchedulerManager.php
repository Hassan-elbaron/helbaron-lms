<?php

namespace App\Domains\Live\Reminders;

use App\Domains\Live\Contracts\ReminderScheduler;
use Illuminate\Contracts\Container\Container;

class ReminderSchedulerManager
{
    public function __construct(private readonly Container $app) {}

    public function resolve(): ReminderScheduler
    {
        return $this->app->make(FakeReminderScheduler::class);
    }
}
