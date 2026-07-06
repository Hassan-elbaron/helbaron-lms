<?php

namespace App\Domains\Live\Calendar\Data;

use Carbon\CarbonInterface;

final readonly class CalendarEvent
{
    /** @param array<int, string> $attendees */
    public function __construct(
        public string $title,
        public CarbonInterface $startsAt,
        public CarbonInterface $endsAt,
        public string $timezone,
        public array $attendees = [],
    ) {}
}
