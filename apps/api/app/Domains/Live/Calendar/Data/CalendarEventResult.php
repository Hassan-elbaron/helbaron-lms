<?php

namespace App\Domains\Live\Calendar\Data;

final readonly class CalendarEventResult
{
    public function __construct(
        public string $provider,
        public ?string $externalId = null,
        public ?string $htmlLink = null,
    ) {}
}
