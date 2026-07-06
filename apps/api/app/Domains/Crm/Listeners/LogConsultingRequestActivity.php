<?php

namespace App\Domains\Crm\Listeners;

use App\Domains\Crm\Enums\ActivityType;
use App\Domains\Crm\Events\ConsultingRequestCreated;
use App\Domains\Crm\Services\ActivityLogger;

/**
 * Appends a timeline entry when a consulting request is created.
 */
class LogConsultingRequestActivity
{
    public function __construct(private readonly ActivityLogger $log) {}

    public function handle(ConsultingRequestCreated $event): void
    {
        $this->log->log($event->request, ActivityType::System, 'Consulting request submitted', $event->request->requested_by);
    }
}
