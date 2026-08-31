<?php

/*
 | Notifications configuration. Consumer domain: reacts to producer events and delivers through the
 | channel/provider abstractions. Providers default to 'fake' so local/test never send for real; a
 | channel with a fake/unconfigured provider is reported truthfully (Skipped/Failed), never Sent.
 | All delivery is queued.
 */
return [

    // Explicit escape hatch for a deliberate non-delivery environment. Production validation reads
    // this value before allowing the fake provider adapters used by demos and preview deployments.
    'allow_fake_providers' => (bool) env('NOTIFICATIONS_ALLOW_FAKE', false),

    'locale' => [
        'default' => env('APP_LOCALE', 'en'),
        'fallback' => env('APP_FALLBACK_LOCALE', 'en'),
    ],
    'retry' => [
        'max_attempts' => (int) env('NOTIFICATIONS_MAX_ATTEMPTS', 3),
        'backoff_seconds' => [10, 60, 300],
    ],
    'rate_limit' => [
        'per_minute' => (int) env('NOTIFICATIONS_RATE_PER_MINUTE', 30),
        // How long a rate-limited delivery waits before a fresh attempt. The deferral re-dispatches
        // the job and consumes neither a delivery attempt nor a job try, so rate limiting can never
        // dead-letter a message that was never actually attempted.
        'retry_after_seconds' => (int) env('NOTIFICATIONS_RATE_RETRY_AFTER', 30),
    ],
    // Real provider selection per channel. Defaults to 'fake' so local/test never send.
    'providers' => [
        'mail' => env('NOTIFICATIONS_MAIL_PROVIDER', 'fake'),   // fake | mailgun
        'sms' => env('NOTIFICATIONS_SMS_PROVIDER', 'fake'),     // fake | twilio
        'push' => env('NOTIFICATIONS_PUSH_PROVIDER', 'fake'),   // fake | firebase
    ],
    // Per-channel enable toggles — independently configurable (H6). A disabled channel is recorded
    // as Skipped (Disabled), never Sent. In-app is always on (the notification row is the delivery).
    // Webhooks is off and stays off: registered for contract completeness, transport deferred to
    // ADR-16. Enablement lives here rather than in the platform feature-flag service to keep the
    // Notifications context free of a dependency on the Features module (Deptrac boundary).
    'channels' => [
        'email' => ['enabled' => env('NOTIFICATIONS_CHANNEL_EMAIL', true)],
        'sms' => ['enabled' => env('NOTIFICATIONS_CHANNEL_SMS', true)],
        'whatsapp' => ['enabled' => env('NOTIFICATIONS_CHANNEL_WHATSAPP', true)],
        'push' => ['enabled' => env('NOTIFICATIONS_CHANNEL_PUSH', true)],
        'webhooks' => ['enabled' => false],
    ],

    'queue' => env('NOTIFICATIONS_QUEUE', 'notifications'),
    'default_channels' => ['in_app'],

    // Marketing engine (campaigns / drip / automation). Quiet hours apply to the MARKETING category
    // ONLY: a marketing message due inside [start, end) in the recipient's timezone is deferred to the
    // window end, never dropped. Transactional/critical messages ignore this entirely. A per-user
    // quiet-hours preference (user_notification_settings) overrides these defaults for that user.
    'marketing' => [
        'quiet_hours' => [
            'enabled' => (bool) env('MARKETING_QUIET_HOURS_ENABLED', true),
            'start' => env('MARKETING_QUIET_HOURS_START', '21:00'),
            'end' => env('MARKETING_QUIET_HOURS_END', '08:00'),
        ],
    ],
    /*
     | DIGEST DELIVERY — OFF, and off is the honest answer today.
     |
     | `digest_frequency` validated, persisted, came back from the API and was documented in
     | openapi/notifications.yaml. A user could select "daily" and nothing would ever send one:
     | DigestService::pendingForUserId() has zero callers, there is no scheduler entry, and there is
     | no delivery path. A control that does nothing is worse than an absent one, because the user
     | believes they have configured something.
     |
     | While this is false the setting is not offered, not accepted and not returned. The COLUMN and
     | the enum stay, so nobody's stored choice is destroyed and turning this on is a one-line change
     | once the delivery side exists.
     |
     | This key previously read `'enabled' => true` — hardcoded, read by nothing, and asserting the
     | opposite of the truth. That is the config-file half of the same defect.
     |
     | BEFORE SETTING THIS TRUE, the following must exist — this is not a checklist of nice-to-haves,
     | it is what separates a digest from a duplicate-email incident:
     |   - a scheduled command, and a scheduler that actually runs it;
     |   - per-user timezone windows (users already store a timezone; a 09:00 digest means their 09:00);
     |   - a dedup ledger keyed on (user, period), so a re-run, a retry or a deploy mid-run cannot
     |     send the same digest twice;
     |   - retry and failure handling that does not silently drop a period;
     |   - bilingual templates, since this product ships in English and Arabic;
     |   - operator controls (pause, dry-run) and an audit trail.
     */
    'digest' => [
        'enabled' => (bool) env('NOTIFICATIONS_DIGEST_ENABLED', false),
    ],

    // H4 — async fan-out. A large recipient set is split into chunks of this size, and each chunk
    // is a queued, retry-safe job dispatched as one Bus batch, so a 10k-learner announcement never
    // runs in the HTTP request. Tune down for tighter per-job bounds, up for fewer jobs.
    'fanout' => [
        'chunk_size' => max(1, (int) env('NOTIFICATIONS_FANOUT_CHUNK', 500)),
    ],
];
