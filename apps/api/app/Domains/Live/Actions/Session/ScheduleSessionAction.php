<?php

namespace App\Domains\Live\Actions\Session;

use App\Domains\Live\Calendar\Data\CalendarEvent;
use App\Domains\Live\Contracts\CalendarProvider;
use App\Domains\Live\Contracts\MeetingProvider;
use App\Domains\Live\Enums\LiveSessionStatus;
use App\Domains\Live\Events\SessionScheduled;
use App\Domains\Live\Meeting\Data\MeetingRequest;
use App\Domains\Live\Models\LiveSession;
use App\Domains\Live\Services\TimezoneService;
use App\Platform\Shared\Actions\BaseAction;

/**
 * Schedules a live session: validates timezone, creates a meeting (Fake provider) + a calendar
 * event (Null provider), and assigns trainers. Reminders are scheduled by a listener on
 * SessionScheduled.
 */
class ScheduleSessionAction extends BaseAction
{
    public function __construct(
        private readonly TimezoneService $timezones,
        private readonly MeetingProvider $meeting,
        private readonly CalendarProvider $calendar,
    ) {}

    /**
     * @param  array<string, mixed>  $data  starts_at (local), timezone, duration_minutes, title, ...
     */
    public function execute(array $data): LiveSession
    {
        $timezone = (string) ($data['timezone'] ?? config('live.default_timezone'));
        $this->timezones->assertValid($timezone);

        $startsUtc = $this->timezones->toUtc((string) $data['starts_at'], $timezone);
        $duration = (int) ($data['duration_minutes'] ?? 60);
        $endsUtc = $startsUtc->addMinutes($duration);

        $session = $this->transaction(function () use ($data, $timezone, $startsUtc, $endsUtc, $duration): LiveSession {
            $meeting = $this->meeting->create(new MeetingRequest(
                title: (string) $data['title'],
                startsAt: $startsUtc,
                durationMinutes: $duration,
                timezone: $timezone,
            ));

            $session = LiveSession::create([
                'live_course_id' => $data['live_course_id'] ?? null,
                'series_id' => $data['series_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status' => LiveSessionStatus::Scheduled->value,
                'timezone' => $timezone,
                'starts_at' => $startsUtc,
                'ends_at' => $endsUtc,
                'capacity' => $data['capacity'] ?? null,
                'waiting_room' => $data['waiting_room'] ?? true,
                'meeting_provider' => $meeting->provider,
                'meeting_external_id' => $meeting->externalId,
                'join_url' => $meeting->joinUrl,
            ]);

            if (! empty($data['trainer_ids'])) {
                $session->syncTrainers($data['trainer_ids']);
            }

            $this->calendar->createEvent(new CalendarEvent(
                title: $session->title,
                startsAt: $startsUtc,
                endsAt: $endsUtc,
                timezone: $timezone,
            ));

            return $session;
        });

        SessionScheduled::dispatch($session);

        return $session;
    }
}
