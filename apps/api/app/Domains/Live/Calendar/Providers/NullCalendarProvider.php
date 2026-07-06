<?php

namespace App\Domains\Live\Calendar\Providers;

use App\Domains\Live\Calendar\Data\CalendarEvent;
use App\Domains\Live\Calendar\Data\CalendarEventResult;
use App\Domains\Live\Contracts\CalendarProvider;

/**
 * No-op calendar provider (default). Real Google/Outlook adapters are future work.
 */
class NullCalendarProvider implements CalendarProvider
{
    public function createEvent(CalendarEvent $event): CalendarEventResult
    {
        return new CalendarEventResult(provider: 'null');
    }
}
