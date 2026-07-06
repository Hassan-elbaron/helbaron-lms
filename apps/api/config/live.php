<?php

/*
 | Live Learning configuration. Meeting/calendar/reminder integrations go through abstractions.
 | Only the Fake meeting provider is implemented (no Zoom/Teams/Meet SDKs).
 */
return [
    'default_timezone' => env('LIVE_DEFAULT_TIMEZONE', 'UTC'),

    'meeting' => [
        'provider' => env('LIVE_MEETING_PROVIDER', 'fake'), // fake (only implemented)
    ],
    'calendar' => [
        'provider' => env('LIVE_CALENDAR_PROVIDER', 'null'), // null (abstraction only)
    ],
    'reminder' => [
        'provider' => env('LIVE_REMINDER_PROVIDER', 'fake'),
        'offsets_minutes' => [1440, 60], // 1 day and 1 hour before
    ],

    'join' => [
        'token_ttl_minutes' => 120,
        'window_minutes_before' => 15, // waiting room opens this many minutes before start
    ],
];
