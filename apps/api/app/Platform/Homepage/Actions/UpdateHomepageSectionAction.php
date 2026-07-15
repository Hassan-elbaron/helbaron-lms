<?php

namespace App\Platform\Homepage\Actions;

use App\Platform\Homepage\Models\HomepageSection;
use App\Platform\Shared\Audit\AuditLogger;

/**
 * Thin orchestrator for privileged mutations of a HomepageSection. RichText sanitization and version
 * snapshotting live in the model's saving/updated hooks (the single write-time points), so this
 * Action only applies the change and writes the matching audit-trail entry. Mirrors
 * App\Platform\Pages\Actions\UpdateStaticPageAction. No business logic beyond that — keep it lean.
 */
class UpdateHomepageSectionAction
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Apply an editorial update. Sanitization + version snapshot happen in the model hooks.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(HomepageSection $section, array $attributes): HomepageSection
    {
        $section->fill($attributes)->save();

        $this->audit->log('homepage_section.updated', $section, ['key' => $section->key]);

        return $section;
    }

    /** Publish the block now (snapshotting the draft) and record the audit entry. */
    public function publish(HomepageSection $section): HomepageSection
    {
        $section->publish();

        $this->audit->log('homepage_section.published', $section, ['key' => $section->key]);

        return $section;
    }

    /** Restore a prior version (creating a fresh version) and record the audit entry. */
    public function rollback(HomepageSection $section, int $version): HomepageSection
    {
        $section->rollbackTo($version);

        $this->audit->log('homepage_section.rolled_back', $section, ['key' => $section->key, 'version' => $version]);

        return $section;
    }
}
