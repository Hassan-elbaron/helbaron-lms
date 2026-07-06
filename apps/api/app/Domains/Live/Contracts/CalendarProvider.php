<?php

namespace App\Domains\Live\Contracts;

use App\Domains\Live\Calendar\Data\CalendarEvent;
use App\Domains\Live\Calendar\Data\CalendarEventResult;

/**
 * Calendar integration abstraction. Only a Null (no-op) implementation exists here — Google/
 * Outlook adapters are future work and reference no SDK in this domain.
 */
interface CalendarProvider
{
    public function createEvent(CalendarEvent $event): CalendarEventResult;
}
