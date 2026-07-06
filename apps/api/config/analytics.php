<?php

/*
 | Analytics configuration. This domain is a READ-MODEL consumer — it reads metric_snapshots,
 | never operational tables. The metrics catalog below defines what can be measured.
 */
return [
    'cache' => [
        'ttl_seconds' => (int) env('ANALYTICS_CACHE_TTL', 300),
    ],
    'export' => [
        'disk' => env('ANALYTICS_EXPORT_DISK', 'local'),
        'download_ttl_minutes' => 15,
    ],
    'default_granularity' => 'daily',

    // key => definition. Populated into metric_definitions by the seeder.
    'metrics' => [
        'signups' => ['name' => 'Signups', 'category' => 'general', 'unit' => 'count'],
        'enrollments' => ['name' => 'Enrollments', 'category' => 'enrollment', 'unit' => 'count'],
        'completions' => ['name' => 'Course Completions', 'category' => 'completion', 'unit' => 'count'],
        'revenue' => ['name' => 'Revenue', 'category' => 'revenue', 'unit' => 'currency_minor'],
        'orders_paid' => ['name' => 'Paid Orders', 'category' => 'commerce', 'unit' => 'count'],
        'certificates_issued' => ['name' => 'Certificates Issued', 'category' => 'certification', 'unit' => 'count'],
        'live_sessions_completed' => ['name' => 'Live Sessions Completed', 'category' => 'live', 'unit' => 'count'],
        'consulting_requests' => ['name' => 'Consulting Requests', 'category' => 'crm', 'unit' => 'count'],
    ],
];
