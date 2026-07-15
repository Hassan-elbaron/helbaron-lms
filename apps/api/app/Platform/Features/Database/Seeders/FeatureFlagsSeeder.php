<?php

namespace App\Platform\Features\Database\Seeders;

use App\Platform\Features\Models\FeatureFlag;
use Illuminate\Database\Seeder;

/**
 * Seeds the domain feature flags, ALL ENABLED by default, so the platform behaves exactly as it
 * does today (flags are additive — turning one OFF is an explicit admin choice). Idempotent:
 * firstOrCreate on `key`, so re-running never duplicates or overwrites admin edits.
 */
class FeatureFlagsSeeder extends Seeder
{
    /** @var array<string, string> Flag key => human-readable name. */
    private const FLAGS = [
        'commerce' => 'Commerce',
        'crm' => 'CRM',
        'live_sessions' => 'Live Sessions',
        'events' => 'Events',
        'certificates' => 'Certificates',
        'organizations' => 'Organizations',
        'instructor_portal' => 'Instructor Portal',
        'blog' => 'Blog',
        'consulting' => 'Consulting',
        'b2b' => 'B2B',
        'notifications' => 'Notifications',
        'analytics' => 'Analytics',
        'reports' => 'Reports',
        'search' => 'Search',
        'ai_features' => 'AI Features',
        'experimental' => 'Experimental',
    ];

    public function run(): void
    {
        foreach (self::FLAGS as $key => $name) {
            FeatureFlag::firstOrCreate(
                ['key' => $key],
                [
                    'name' => $name,
                    'is_enabled' => true,
                    'owner' => 'platform',
                ],
            );
        }
    }
}
