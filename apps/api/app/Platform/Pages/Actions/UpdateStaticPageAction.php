<?php

namespace App\Platform\Pages\Actions;

use App\Platform\Pages\Models\StaticPage;
use App\Platform\Shared\Audit\AuditLogger;

/**
 * Thin orchestrator for privileged mutations of a StaticPage. Body sanitization and version
 * snapshotting live in the model's saving/updated hooks (the single write-time points), so this
 * Action only applies the change and writes the matching audit-trail entry. No business logic
 * beyond that — keep it lean.
 */
class UpdateStaticPageAction
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Apply an editorial update. Sanitization + version snapshot happen in the model hooks.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(StaticPage $page, array $attributes): StaticPage
    {
        $page->fill($attributes)->save();

        $this->audit->log('static_page.updated', $page, ['slug' => $page->slug]);

        return $page;
    }

    /** Publish the page now and record the audit entry. */
    public function publish(StaticPage $page): StaticPage
    {
        $page->publish();

        $this->audit->log('static_page.published', $page, ['slug' => $page->slug]);

        return $page;
    }

    /** Restore a prior version (creating a fresh version) and record the audit entry. */
    public function rollback(StaticPage $page, int $version): StaticPage
    {
        $page->rollbackTo($version);

        $this->audit->log('static_page.rolled_back', $page, ['slug' => $page->slug, 'version' => $version]);

        return $page;
    }
}
