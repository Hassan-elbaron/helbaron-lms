<?php

/*
 | Feature flags (kill-switches). Config-driven and env-overridable so capabilities can be
 | toggled per environment without a deploy. Read via App\Shared\Support\Features::enabled().
 | No feature is defined at the foundation stage — this is the registry only.
 */
return [
    'flags' => [
        // 'example_feature' => env('FEATURE_EXAMPLE', false),
    ],
];
