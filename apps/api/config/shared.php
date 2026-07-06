<?php

/*
 | Shared foundation configuration. Cross-cutting defaults consumed by the shared kernel
 | (locales, money, pagination, ids). No business/domain values here.
 */
return [
    // Locales
    'locales' => ['en', 'ar'],
    'default_locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'rtl_locales' => ['ar'],

    // Money
    'money' => [
        'default_currency' => env('DEFAULT_CURRENCY', 'SAR'),
        'minor_unit_scale' => 2,
    ],

    // Pagination
    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
    ],

    // Identifiers
    'ids' => [
        'public_id_version' => 7, // UUIDv7 (time-ordered)
    ],
];
