<?php

namespace App\Domains\Crm\Listeners;

use App\Domains\Crm\Enums\ActivityType;
use App\Domains\Crm\Events\LeadCreated;
use App\Domains\Crm\Services\ActivityLogger;

/**
 * Appends a timeline entry when a lead is created.
 */
class LogLeadCreatedActivity
{
    public function __construct(private readonly ActivityLogger $log) {}

    public function handle(LeadCreated $event): void
    {
        $this->log->log($event->lead, ActivityType::System, 'Lead created', $event->lead->owner_id);
    }
}
