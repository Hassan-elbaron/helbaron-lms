<?php

use App\Platform\Shared\Support\Env;

/*
 | Identity domain configuration (OTP, lockout, MFA, password reset). No secrets here —
 | tunables only. Consumed by Identity services; overridable via env.
 */
return [
    'otp' => [
        'email' => [
            'length' => 6,
            'ttl_minutes' => (int) env('IDENTITY_OTP_EMAIL_TTL', 10),
            'max_per_hour' => (int) env('IDENTITY_OTP_EMAIL_MAX_PER_HOUR', 5),
        ],
        'sms' => [
            'length' => 6,
            'ttl_minutes' => (int) env('IDENTITY_OTP_SMS_TTL', 10),
            'max_per_hour' => (int) env('IDENTITY_OTP_SMS_MAX_PER_HOUR', 5),
        ],
    ],

    'lockout' => [
        'max_attempts' => (int) env('IDENTITY_LOGIN_MAX_ATTEMPTS', 5),
        'minutes' => (int) env('IDENTITY_LOGIN_LOCK_MINUTES', 15),
    ],

    /*
     | Session lifetimes — the ONE place both halves of "remember me" are defined.
     |
     | The login form has always offered the checkbox and the system has never honoured it: the token
     | and the cookie were both issued for a fixed fourteen days whether it was ticked or not. The
     | dangerous direction is the unticked one — somebody deliberately declining to be remembered on a
     | shared machine still walked away leaving a fortnight-long credential behind them.
     |
     | `remembered_days` is the persistent choice. `session_hours` is the ceiling on a token issued
     | WITHOUT remember-me: the browser cookie for that case carries no Max-Age at all, so it dies
     | when the browser closes, and this bounds the token itself for the case where it leaks anyway.
     | A cookie the browser discards is not the same as a credential the server has stopped accepting.
     */
    'session' => [
        'remembered_days' => (int) env('IDENTITY_SESSION_REMEMBER_DAYS', 30),
        'session_hours' => (int) env('IDENTITY_SESSION_HOURS', 12),
    ],

    'mfa' => [
        // Shown as the account label inside the user's authenticator app, so it must name THIS
        // instance's academy rather than the vendor.
        // Env::string at every level: each of these keys ships present-but-empty in .env.example,
        // and env()'s default argument does not fire for ''. Shown inside the user's authenticator app.
        'issuer' => Env::string(
            'IDENTITY_MFA_ISSUER',
            fn (): string => Env::string(
                'BRAND_NAME_EN',
                fn (): string => Env::string('APP_NAME', 'Academy'),
            ),
        ),
        'recovery_code_count' => 8,
        'window' => 1, // TOTP time-step tolerance
    ],

    'password_reset' => [
        'expires_minutes' => (int) env('IDENTITY_PASSWORD_RESET_TTL', 30),
    ],
];
