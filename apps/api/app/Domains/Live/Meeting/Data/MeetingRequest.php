<?php

namespace App\Domains\Live\Meeting\Data;

use Carbon\CarbonInterface;

final readonly class MeetingRequest
{
    public function __construct(
        public string $title,
        public CarbonInterface $startsAt,
        public int $durationMinutes,
        public string $timezone,
        public ?string $hostEmail = null,
    ) {}
}
