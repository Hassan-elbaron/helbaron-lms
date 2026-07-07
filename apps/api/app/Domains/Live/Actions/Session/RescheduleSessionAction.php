<?php

namespace App\Domains\Live\Actions\Session;

use App\Domains\Live\Events\SessionRescheduled;
use App\Domains\Live\Models\LiveSession;
use App\Domains\Live\Services\TimezoneService;
use App\Platform\Shared\Actions\BaseAction;

class RescheduleSessionAction extends BaseAction
{
    public function __construct(private readonly TimezoneService $timezones) {}

    /** @param array<string, mixed> $data starts_at (local), duration_minutes, timezone? */
    public function execute(LiveSession $session, array $data): LiveSession
    {
        $timezone = (string) ($data['timezone'] ?? $session->timezone);
        $this->timezones->assertValid($timezone);

        $startsUtc = $this->timezones->toUtc((string) $data['starts_at'], $timezone);
        $duration = (int) ($data['duration_minutes'] ?? $session->starts_at->diffInMinutes($session->ends_at));

        $session = $this->transaction(function () use ($session, $timezone, $startsUtc, $duration): LiveSession {
            $session->forceFill([
                'timezone' => $timezone,
                'starts_at' => $startsUtc,
                'ends_at' => $startsUtc->addMinutes($duration),
            ])->save();

            return $session;
        });

        SessionRescheduled::dispatch($session);

        return $session;
    }
}
