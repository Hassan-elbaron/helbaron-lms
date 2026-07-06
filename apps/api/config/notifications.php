<?php

/*
 | Notifications configuration. Consumer domain: reacts to producer events, delivers via Fake
 | channel/provider abstractions ONLY (no SES/Mailgun/Twilio/Firebase/WhatsApp). Never sends
 | real messages. All delivery is queued.
 */
return [
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
    ],
    // Real provider selection per channel. Defaults to 'fake' so local/test never send.
    'providers' => [
        'mail' => env('NOTIFICATIONS_MAIL_PROVIDER', 'fake'),   // fake | mailgun
        'sms' => env('NOTIFICATIONS_SMS_PROVIDER', 'fake'),     // fake | twilio
        'push' => env('NOTIFICATIONS_PUSH_PROVIDER', 'fake'),   // fake | firebase
    ],

    'queue' => env('NOTIFICATIONS_QUEUE', 'notifications'),
    'default_channels' => ['in_app'],
    'digest' => [
        'enabled' => true,
    ],
];
