<?php

declare(strict_types=1);

/*
 | Demo environment configuration.
 |
 | Drives the `demo:seed` command and DemoSeeder. Disabled by default; must be explicitly
 | enabled via DEMO_MODE=true and NEVER runs in the `production` environment (the command
 | refuses regardless of this flag). All demo records are identifiable via the stable markers
 | below (email domain, slug prefix, coupon prefix) so they can be found without a schema column.
 */

return [
    // Master switch. The demo seeder refuses to run unless this is true.
    'enabled' => env('DEMO_MODE', false),

    // Deterministic seed so re-runs and screenshots are reproducible.
    'seed' => (int) env('DEMO_SEED', 20260711),

    // A destructive reset (purge + reseed) requires this to be explicitly true AND non-production.
    'reset_allowed' => env('DEMO_RESET_ALLOWED', false),

    // When true, video lessons carry an external embed reference (see `media` manifest). When
    // false, video lessons fall back to their reading summary only (fully self-contained/offline).
    'external_media' => env('DEMO_EXTERNAL_MEDIA', true),

    /*
     | Stable markers used to identify and idempotently upsert demo records. These are business
     | keys (not a new schema column): a dedicated email domain, a course/product slug prefix,
     | and a coupon-code prefix. Anything carrying these is demo data.
     */
    'markers' => [
        'email_domain' => env('DEMO_EMAIL_DOMAIN', 'demo.helbaron.local'),
        'slug_prefix' => 'demo-',
        'coupon_prefix' => 'DEMO',
        'password' => env('DEMO_USER_PASSWORD', 'password'),
        'currency' => env('DEMO_CURRENCY', 'SAR'),
    ],

    /*
     | Scale profile. `showcase` is the modest, fast, CI/validation-safe default. `enterprise`
     | generates a large, realistic dataset that makes every dashboard/table/chart/filter look
     | like a platform that has run for years. Select via DEMO_SCALE. Counts are targets the
     | seeder honours using factories/chunked bulk inserts for high-volume leaf data (the repo's
     | seeding convention) and real domain actions for the entitlement/progress/certificate seams.
     |
     | Runtime note: `enterprise` writes tens of thousands of rows and is intended to run on a
     | provisioning host, not in CI. Keep the default `showcase` for tests/CI.
     */
    'scale' => env('DEMO_SCALE', 'showcase'),

    'profiles' => [
        'showcase' => [
            'instructors' => 6,
            'students' => 24,
            'course_variants' => 1,      // 14 blueprint courses × 1 = 14
            'sections_per_course' => [2, 2],
            'lessons_per_section' => [3, 3],
            'enrollments_per_student' => [2, 5],
            'completion_rate' => 0.30,   // fraction of enrollments driven to completion (certificate)
            'bookmarks_per_student' => [1, 4],
            'notes_per_student' => [1, 4],
            'organizations' => 1,
            'members_per_org' => [4, 6],
            'orders' => 36,
            'refund_rate' => 0.05,
            'coupons' => 2,
            'live_sessions' => 2,
            'registrations_per_session' => [6, 12],
            'crm' => ['companies' => 4, 'contacts' => 8, 'leads' => 6, 'opportunities' => 4, 'activities' => 20, 'notes' => 16],
            'notification_templates_email' => 0,   // base in-app set only
            'notifications_per_student' => [2, 4],
            'metric_days' => 30,
            'audit' => 1,
        ],
        'enterprise' => [
            'instructors' => 16,
            'students' => 600,
            'course_variants' => 4,      // 14 blueprints × 4 cohorts = 56 courses (bilingual, dated)
            'sections_per_course' => [4, 7],
            'lessons_per_section' => [4, 8],
            'enrollments_per_student' => [4, 12],
            'completion_rate' => 0.28,
            'bookmarks_per_student' => [3, 9],
            'notes_per_student' => [4, 12],
            'organizations' => 40,
            'members_per_org' => [6, 25],
            'orders' => 1500,
            'refund_rate' => 0.07,
            'coupons' => 24,
            'live_sessions' => 60,
            'registrations_per_session' => [15, 60],
            'crm' => ['companies' => 250, 'contacts' => 1000, 'leads' => 800, 'opportunities' => 400, 'activities' => 5000, 'notes' => 4000],
            'notification_templates_email' => 25,  // adds email-channel templates (25+)
            'notifications_per_student' => [6, 14],
            'metric_days' => 365,
            'audit' => 20000,
        ],
    ],

    /*
     | External media manifest (STEP 3 sourcing policy). URLs ONLY — nothing is ever downloaded.
     |
     | IMAGES: royalty-free Unsplash photos (Unsplash License: free to use commercially, no
     | attribution required, no permission needed). Referenced by their stable photo IDs and rendered
     | on the Unsplash CDN with sizing params, so course covers, avatars, partner logos and event
     | banners look like a mature, populated product.
     |
     | VIDEOS: canonical public educational talks (well-known TED / TEDx lessons) referenced by their
     | YouTube watch/embed URLs. These play under standard YouTube terms; embedding permission MUST be
     | re-verified before any NON-demo/production use (see docs/demo/DEMO_CONTENT_SOURCING_POLICY.md).
     | Every video lesson ALSO ships an original reading fallback, so the demo stays fully usable if
     | `external_media` is false or an embed ever becomes unavailable.
     */
    'media' => [
        'poster_fallback' => 'demo/posters/course-cover.svg',

        // Unsplash image manifest. `base` + a photo ID + the relevant `*_params` = a ready CDN URL.
        'images' => [
            'base' => 'https://images.unsplash.com/photo-',
            'cover_params' => '?auto=format&fit=crop&w=1200&q=70',
            'avatar_params' => '?auto=format&fit=facearea&facepad=3&w=256&h=256&q=70',
            'banner_params' => '?auto=format&fit=crop&w=1600&q=70',
            'logo_params' => '?auto=format&fit=crop&w=320&h=160&q=70',

            // Course covers, themed per vertical (business / education imagery).
            'covers' => [
                'project-management' => ['1454165804606-c3d57bc86b40', '1517245386807-bb43f82c33c4'],
                'agile-mindset' => ['1542744173-8e7e53415bb0', '1531403009284-440f080d1e12'],
                'business-development' => ['1552664730-d307ca884978', '1556761175-5973dc0f32e7'],
                'business-strategies' => ['1486406146926-c627a92ad1ab', '1507679799987-c73779587ccf'],
                'entrepreneurship' => ['1519389950473-47ba0277781c', '1522202176988-66273c2fd55f'],
                'business-skills' => ['1521737604893-d14cc237f11d', '1543269865-cbf427effbad'],
                'leadership' => ['1531973576160-7125cd663d86', '1600880292203-757bb62b4baf'],
                'marketing-strategies' => ['1460925895917-afdab827c52f', '1557804506-669a67965ba0'],
                'sales-management' => ['1552581234-26160f608093', '1517048676732-d65bc937f952'],
                'finance-analysis' => ['1553877522-43269d4ea984', '1590283603385-17ffb3a7f29f'],
                'business-ai' => ['1620712943543-bcc4688e7485', '1550751827-4bd374c3f58b'],
                'investment-trading' => ['1611974789855-9c2a0a7236a3', '1590283603385-17ffb3a7f29f'],
            ],

            // Portrait photos for user avatars / instructor headshots.
            'avatars' => [
                '1500648767791-00dcc994a43e', '1494790108377-be9c29b29330', '1507003211169-0a1dd7228f2d',
                '1438761681033-6461ffad8d80', '1472099645785-5658abf4ff4e', '1544005313-94ddf0286df2',
                '1519085360753-af0119f7cbe7', '1487412720507-e7ab37603c6f', '1506794778202-cad84cf45f1d',
                '1534528741775-53994a69daeb', '1519345182560-3f2917c472ef', '1508214751196-bcfd4ca60f91',
                '1517841905240-472988babdf9', '1531427186611-ecfd6d936c79', '1524504388940-b1c1722653e1',
                '1489424731084-a5d8b219a5bb',
            ],

            // Partner / client logo-cloud stand-ins (buildings / brand-neutral imagery).
            'logos' => [
                '1486406146926-c627a92ad1ab', '1497366216548-37526070297c', '1554469384-e58fbf5ca10e',
                '1497366811353-6870744d04b2', '1500382017468-9049fed747ef', '1460472178825-e5240623afd5',
                '1577760258779-e787a1733016', '1541888946425-d81bb19240f5', '1502005229762-cf1b2da7c5d6',
            ],

            // Live-event / hero banners.
            'banners' => [
                '1531058020387-3be344556be6', '1505373877841-8d25f7d46678', '1540575467063-178a50c2df87',
                '1511578314322-379afb476865', '1475721027785-f74eccf877e2', '1591115765373-5207764f72e7',
            ],

            'hero' => '1522071820081-009f0129c71c',
        ],

        // Canonical public educational talks (YouTube). provider, video_id, watch url, embed url,
        // title, author, license note, attribution, verification status, added_at, fallback.
        'videos' => [
            ['key' => 'procrastination', 'provider' => 'youtube', 'video_id' => 'arj7oStGLkU', 'url' => 'https://www.youtube.com/watch?v=arj7oStGLkU', 'embed_url' => 'https://www.youtube.com/embed/arj7oStGLkU', 'title' => 'Inside the mind of a master procrastinator', 'author' => 'Tim Urban (TED)', 'platform' => 'youtube', 'license' => 'Standard YouTube — re-verify embedding before non-demo use', 'attribution' => 'Tim Urban, TED. Public talk referenced for demo purposes.', 'embeddable' => 'public', 'added_at' => '2026-07-14', 'fallback' => 'reading'],
            ['key' => 'inspire-action', 'provider' => 'youtube', 'video_id' => 'qp0HIF3SfI4', 'url' => 'https://www.youtube.com/watch?v=qp0HIF3SfI4', 'embed_url' => 'https://www.youtube.com/embed/qp0HIF3SfI4', 'title' => 'How great leaders inspire action', 'author' => 'Simon Sinek (TEDx)', 'platform' => 'youtube', 'license' => 'Standard YouTube — re-verify embedding before non-demo use', 'attribution' => 'Simon Sinek, TEDxPugetSound. Public talk referenced for demo purposes.', 'embeddable' => 'public', 'added_at' => '2026-07-14', 'fallback' => 'reading'],
            ['key' => 'speak-listen', 'provider' => 'youtube', 'video_id' => 'eIho2S0ZahI', 'url' => 'https://www.youtube.com/watch?v=eIho2S0ZahI', 'embed_url' => 'https://www.youtube.com/embed/eIho2S0ZahI', 'title' => 'How to speak so that people want to listen', 'author' => 'Julian Treasure (TED)', 'platform' => 'youtube', 'license' => 'Standard YouTube — re-verify embedding before non-demo use', 'attribution' => 'Julian Treasure, TED. Public talk referenced for demo purposes.', 'embeddable' => 'public', 'added_at' => '2026-07-14', 'fallback' => 'reading'],
            ['key' => 'motivation', 'provider' => 'youtube', 'video_id' => 'rrkrvAUbU9Y', 'url' => 'https://www.youtube.com/watch?v=rrkrvAUbU9Y', 'embed_url' => 'https://www.youtube.com/embed/rrkrvAUbU9Y', 'title' => 'The puzzle of motivation', 'author' => 'Dan Pink (TED)', 'platform' => 'youtube', 'license' => 'Standard YouTube — re-verify embedding before non-demo use', 'attribution' => 'Dan Pink, TED. Public talk referenced for demo purposes.', 'embeddable' => 'public', 'added_at' => '2026-07-14', 'fallback' => 'reading'],
            ['key' => 'grit', 'provider' => 'youtube', 'video_id' => 'H14bBuluwB8', 'url' => 'https://www.youtube.com/watch?v=H14bBuluwB8', 'embed_url' => 'https://www.youtube.com/embed/H14bBuluwB8', 'title' => 'Grit: the power of passion and perseverance', 'author' => 'Angela Lee Duckworth (TED)', 'platform' => 'youtube', 'license' => 'Standard YouTube — re-verify embedding before non-demo use', 'attribution' => 'Angela Lee Duckworth, TED. Public talk referenced for demo purposes.', 'embeddable' => 'public', 'added_at' => '2026-07-14', 'fallback' => 'reading'],
            ['key' => 'body-language', 'provider' => 'youtube', 'video_id' => 'Ks-_Mh1QhMc', 'url' => 'https://www.youtube.com/watch?v=Ks-_Mh1QhMc', 'embed_url' => 'https://www.youtube.com/embed/Ks-_Mh1QhMc', 'title' => 'Your body language may shape who you are', 'author' => 'Amy Cuddy (TED)', 'platform' => 'youtube', 'license' => 'Standard YouTube — re-verify embedding before non-demo use', 'attribution' => 'Amy Cuddy, TED. Public talk referenced for demo purposes.', 'embeddable' => 'public', 'added_at' => '2026-07-14', 'fallback' => 'reading'],
            ['key' => 'happy-work', 'provider' => 'youtube', 'video_id' => 'fLJsdqxnZb0', 'url' => 'https://www.youtube.com/watch?v=fLJsdqxnZb0', 'embed_url' => 'https://www.youtube.com/embed/fLJsdqxnZb0', 'title' => 'The happy secret to better work', 'author' => 'Shawn Achor (TED)', 'platform' => 'youtube', 'license' => 'Standard YouTube — re-verify embedding before non-demo use', 'attribution' => 'Shawn Achor, TED. Public talk referenced for demo purposes.', 'embeddable' => 'public', 'added_at' => '2026-07-14', 'fallback' => 'reading'],
            ['key' => 'stress-friend', 'provider' => 'youtube', 'video_id' => 'RcGyVTAoXEU', 'url' => 'https://www.youtube.com/watch?v=RcGyVTAoXEU', 'embed_url' => 'https://www.youtube.com/embed/RcGyVTAoXEU', 'title' => 'How to make stress your friend', 'author' => 'Kelly McGonigal (TED)', 'platform' => 'youtube', 'license' => 'Standard YouTube — re-verify embedding before non-demo use', 'attribution' => 'Kelly McGonigal, TED. Public talk referenced for demo purposes.', 'embeddable' => 'public', 'added_at' => '2026-07-14', 'fallback' => 'reading'],
            ['key' => 'vulnerability', 'provider' => 'youtube', 'video_id' => 'iCvmsMzlF7o', 'url' => 'https://www.youtube.com/watch?v=iCvmsMzlF7o', 'embed_url' => 'https://www.youtube.com/embed/iCvmsMzlF7o', 'title' => 'The skill of self confidence', 'author' => 'Ivan Joseph (TEDx)', 'platform' => 'youtube', 'license' => 'Standard YouTube — re-verify embedding before non-demo use', 'attribution' => 'Ivan Joseph, TEDxRyersonU. Public talk referenced for demo purposes.', 'embeddable' => 'public', 'added_at' => '2026-07-14', 'fallback' => 'reading'],
            ['key' => 'first-secret', 'provider' => 'youtube', 'video_id' => 'N4HdBpkH2XM', 'url' => 'https://www.youtube.com/watch?v=N4HdBpkH2XM', 'embed_url' => 'https://www.youtube.com/embed/N4HdBpkH2XM', 'title' => 'The first secret of great design', 'author' => 'Tony Fadell (TED)', 'platform' => 'youtube', 'license' => 'Standard YouTube — re-verify embedding before non-demo use', 'attribution' => 'Tony Fadell, TED. Public talk referenced for demo purposes.', 'embeddable' => 'public', 'added_at' => '2026-07-14', 'fallback' => 'reading'],
        ],
    ],
];
