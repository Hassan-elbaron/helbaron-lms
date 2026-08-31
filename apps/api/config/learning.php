<?php

/*
 | Learning domain configuration (playback tokens, progress). No business rules here.
 */
return [
    'playback' => [
        // fake (default) | s3 | cloudfront | mux. Media is only ever exposed as a signed URL.
        //
        // NOTE: this is a SEPARATE selector from MEDIA_INGESTION_PROVIDER. Configuring real
        // ingestion (s3/mux) while leaving playback on `fake` produces an instance that accepts
        // uploads and then hands learners signed URLs that stream nothing — which is why
        // ProductionConfigValidator refuses `fake` here in production.
        'provider' => env('LEARNING_PLAYBACK_PROVIDER', 'fake'),
        'ttl_seconds' => (int) env('LEARNING_PLAYBACK_TTL', 600),
        // Deliberate escape hatch for a content-only preview environment with no real media.
        'allow_fake_provider' => (bool) env('LEARNING_PLAYBACK_ALLOW_FAKE', false),
    ],
    'progress' => [
        // A course is complete when 100% of its published lessons are completed.
        'completion_percentage' => 100,
    ],
];
