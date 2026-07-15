<?php

namespace App\Platform\Pages\Http\Resources;

use App\Platform\Pages\Models\StaticPage;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * A static CMS page as seen by the public/preview API. Locale-agnostic: emits both { en, ar }
 * locales for title/body/excerpt and a fully-resolved SEO bag (stored overrides merged over
 * sensible fallbacks derived from the page). In `summary` mode only the list-relevant identity
 * fields are returned (used by the pages index / sitemap feed).
 *
 * @property StaticPage $resource
 */
class StaticPageResource extends BaseResource
{
    public function __construct(StaticPage $resource, private readonly bool $summary = false)
    {
        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $page = $this->resource;

        $base = [
            'id' => $page->public_id,
            'slug' => $page->slug,
            'template' => $page->template->value,
            'title' => $page->title,
            'excerpt' => $page->excerpt,
            'show_in_nav' => $page->show_in_nav,
            'position' => $page->position,
            'status' => $page->status->value,
            'published_at' => $page->published_at?->toIso8601String(),
            'updated_at' => $page->updated_at?->toIso8601String(),
        ];

        if ($this->summary) {
            return $base;
        }

        return $base + [
            'body' => $page->body,
            'hero_image' => $page->hero_image,
            'seo' => $this->resolvedSeo(),
        ];
    }

    /**
     * The stored SEO overrides merged over fallbacks derived from the page content, so the
     * frontend always receives a complete, ready-to-use SEO block.
     *
     * @return array<string, mixed>
     */
    private function resolvedSeo(): array
    {
        $page = $this->resource;
        /** @var array<string, mixed> $seo */
        $seo = $page->seo ?? [];

        return [
            'meta_title' => $seo['meta_title'] ?? $page->title,
            'meta_description' => $seo['meta_description'] ?? $page->excerpt,
            'keywords' => $seo['keywords'] ?? null,
            'canonical' => $seo['canonical'] ?? '/'.ltrim($page->slug, '/'),
            'robots_index' => $seo['robots_index'] ?? true,
            'robots_follow' => $seo['robots_follow'] ?? true,
            'og_title' => $seo['og_title'] ?? ($seo['meta_title'] ?? $page->title),
            'og_description' => $seo['og_description'] ?? ($seo['meta_description'] ?? $page->excerpt),
            'og_image' => $seo['og_image'] ?? $page->hero_image,
            'twitter_card' => $seo['twitter_card'] ?? 'summary_large_image',
            'json_ld' => $seo['json_ld'] ?? null,
        ];
    }
}
