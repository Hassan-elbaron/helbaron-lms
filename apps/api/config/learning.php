<?php

/*
 | Learning domain configuration (playback tokens, progress). No business rules here.
 */
return [
    'playback' => [
        // fake (default) | s3 | cloudfront | mux. Media is only ever exposed as a signed URL.
        'provider' => env('LEARNING_PLAYBACK_PROVIDER', 'fake'),
        'ttl_seconds' => (int) env('LEARNING_PLAYBACK_TTL', 600),
    ],
    'progress' => [
        // A course is complete when 100% of its published lessons are completed.
        'completion_percentage' => 100,
    ],
];
