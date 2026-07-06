<?php

use Illuminate\Support\Str;

/*
 | Horizon supervisors. Dedicated queues keep notifications/exports from starving each other.
 | Retry/timeout are explicit; failed jobs land in failed_jobs and the app's own dead-letter
 | logic (e.g. notification deliveries) handles terminal states.
 */
return [
    'domain' => env('HORIZON_DOMAIN'),
    'path' => env('HORIZON_PATH', 'horizon'),
    'use' => 'default',
    'prefix' => env('HORIZON_PREFIX', Str::slug((string) env('APP_NAME', 'helbaron'), '_').'_horizon:'),

    'middleware' => ['web'],

    'waits' => ['redis:default' => 60],
    'trim' => [
        'recent' => 60, 'pending' => 60, 'completed' => 60,
        'recent_failed' => 10080, 'failed' => 10080, 'monitored' => 10080,
    ],

    'silenced' => [],
    'metrics' => ['trim_snapshots' => ['job' => 24, 'queue' => 24]],
    'fast_termination' => false,
    'memory_limit' => 128,

    'defaults' => [
        'supervisor-default' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => (int) env('HORIZON_DEFAULT_MAX_PROCESSES', 4),
            'minProcesses' => 1,
            'balanceMaxShift' => 1,
            'balanceCooldown' => 3,
            'tries' => (int) env('HORIZON_DEFAULT_TRIES', 3),
            'timeout' => 60,
            'nice' => 0,
        ],
        'supervisor-notifications' => [
            'connection' => 'redis',
            'queue' => ['notifications'],
            'balance' => 'auto',
            'maxProcesses' => (int) env('HORIZON_NOTIFICATIONS_MAX_PROCESSES', 6),
            'minProcesses' => 1,
            'tries' => (int) env('HORIZON_NOTIFICATIONS_TRIES', 3),
            'timeout' => 30,
        ],
        'supervisor-exports' => [
            'connection' => 'redis',
            'queue' => ['exports'],
            'balance' => 'auto',
            'maxProcesses' => (int) env('HORIZON_EXPORTS_MAX_PROCESSES', 2),
            'minProcesses' => 1,
            'tries' => (int) env('HORIZON_EXPORTS_TRIES', 2),
            'timeout' => 300,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-default' => ['maxProcesses' => (int) env('HORIZON_DEFAULT_MAX_PROCESSES', 6)],
            'supervisor-notifications' => ['maxProcesses' => (int) env('HORIZON_NOTIFICATIONS_MAX_PROCESSES', 10)],
            'supervisor-exports' => ['maxProcesses' => (int) env('HORIZON_EXPORTS_MAX_PROCESSES', 3)],
        ],
        'local' => [
            'supervisor-default' => ['maxProcesses' => 3],
            'supervisor-notifications' => ['maxProcesses' => 3],
            'supervisor-exports' => ['maxProcesses' => 1],
        ],
    ],
];
