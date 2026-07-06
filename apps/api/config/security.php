<?php

/*
 | Security response headers + HSTS. Applied by App\Http\Middleware\SecurityHeaders on every
 | response. Kept in config so ops can tune per environment without code changes.
 */
return [

    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => env('SECURITY_FRAME_OPTIONS', 'DENY'),
        'Referrer-Policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'X-Permitted-Cross-Domain-Policies' => 'none',
        'Cross-Origin-Opener-Policy' => 'same-origin',
        'Cross-Origin-Resource-Policy' => 'same-origin',
        'Permissions-Policy' => env('SECURITY_PERMISSIONS_POLICY', 'geolocation=(), microphone=(), camera=(), payment=()'),
    ],

    // JSON-only API: lock everything down by default. Tune if any HTML/asset surface is added.
    'csp' => env('SECURITY_CSP', "default-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'none'"),

    'hsts' => [
        'enabled' => (bool) env('SECURITY_HSTS_ENABLED', true),
        'max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),
        'include_subdomains' => (bool) env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', true),
        'preload' => (bool) env('SECURITY_HSTS_PRELOAD', true),
    ],

];
