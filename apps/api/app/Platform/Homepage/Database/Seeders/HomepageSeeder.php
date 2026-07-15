<?php

namespace App\Platform\Homepage\Database\Seeders;

use App\Platform\Homepage\Enums\BlockType;
use App\Platform\Homepage\Enums\HomepageStatus;
use App\Platform\Homepage\Models\HomepageSection;
use Illuminate\Database\Seeder;

/**
 * Seeds the seven original predefined homepage blocks with on-brand bilingual default content,
 * already published, so the public homepage renders immediately. Idempotent: firstOrCreate on `key`.
 *
 * A couple of expansion blocks (Statistics, CTA) are additionally seeded as DRAFT so they are ready
 * to configure in the builder without changing the live homepage (Draft is excluded from the public
 * published() scope).
 */
class HomepageSeeder extends Seeder
{
    public function run(): void
    {
        foreach (HomepageSection::defaults() as $key => $definition) {
            HomepageSection::firstOrCreate(
                ['key' => $key],
                [
                    'type' => $definition['type'],
                    'position' => $definition['position'],
                    'is_enabled' => true,
                    'status' => HomepageStatus::Published,
                    'content' => $definition['content'],
                    'published_content' => $definition['content'],
                    'published_at' => now(),
                ],
            );
        }

        // Expansion examples, seeded as Draft (not live) so admins can preview/publish deliberately.
        $examples = [
            'statistics_example' => ['type' => BlockType::Statistics, 'position' => 25],
            'cta_example' => ['type' => BlockType::Cta, 'position' => 65],
        ];

        foreach ($examples as $key => $definition) {
            HomepageSection::firstOrCreate(
                ['key' => $key],
                [
                    'type' => $definition['type'],
                    'position' => $definition['position'],
                    'is_enabled' => true,
                    'status' => HomepageStatus::Draft,
                    'content' => $definition['type']->defaultContent(),
                    'published_content' => null,
                    'published_at' => null,
                ],
            );
        }
    }
}
