<?php

/*
 | CORS. Origins are an explicit allow-list from env (comma-separated) — never "*". Credentials
 | are supported for the first-party SPA. Applied to the API + Sanctum cookie routes.
 */
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000')),
    ))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With', 'X-Correlation-ID'],
    'exposed_headers' => ['X-Correlation-ID'],
    'max_age' => (int) env('CORS_MAX_AGE', 3600),
    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', true),
];
