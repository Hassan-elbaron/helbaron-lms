<?php

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

    'mfa' => [
        'issuer' => env('IDENTITY_MFA_ISSUER', 'HElbaron'),
        'recovery_code_count' => 8,
        'window' => 1, // TOTP time-step tolerance
    ],

    'password_reset' => [
        'expires_minutes' => (int) env('IDENTITY_PASSWORD_RESET_TTL', 30),
    ],
];
