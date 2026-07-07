<?php

namespace App\Domains\Live\Services;

use App\Domains\Live\Exceptions\InvalidTimezoneException;
use App\Platform\Shared\Services\BaseService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Timezone helpers. Sessions are stored in UTC; presentation converts into the session zone.
 */
class TimezoneService extends BaseService
{
    public function assertValid(string $timezone): void
    {
        if (! in_array($timezone, timezone_identifiers_list(), true)) {
            throw new InvalidTimezoneException("Invalid timezone: {$timezone}");
        }
    }

    /** Interpret a wall-clock time in a zone and return the UTC instant. */
    public function toUtc(string $localDateTime, string $timezone): CarbonImmutable
    {
        $this->assertValid($timezone);

        return CarbonImmutable::parse($localDateTime, $timezone)->utc();
    }

    /** Present a UTC instant in the session's zone (ISO-8601). */
    public function inZone(CarbonInterface $utc, string $timezone): string
    {
        return CarbonImmutable::parse($utc)->setTimezone($timezone)->toIso8601String();
    }
}
