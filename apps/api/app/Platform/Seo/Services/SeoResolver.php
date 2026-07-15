<?php

namespace App\Platform\Seo\Services;

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Course;
use App\Domains\Live\Models\LiveSession;
use App\Platform\Identity\Models\User;
use App\Platform\Seo\Enums\SeoEntityType;
use App\Platform\Seo\Models\SeoMeta;

/**
 * The SINGLE read path for resolved SEO. This is NOT a second metadata engine — it is a merge +
 * validation layer over a plain override store (seo_metas):
 *
 *   result = seo_metas override  MERGED OVER  entity-derived defaults  MERGED OVER  branding defaults
 *
 * A stored field only wins when it is non-null; an absent field falls through to the derived default,
 * and ultimately to the global branding default. Cross-context reads (Catalog / Live / Identity) used
 * to derive entity defaults are CENTRALIZED here — no other class in the SEO module touches those
 * models — so the coupling stays in one auditable place.
 *
 * Guarantees baked into every resolve(): exactly ONE canonical (validated; falls back to the entity
 * URL when the stored one is unsafe/invalid), and json_ld is emitted only when it is valid.
 */
class SeoResolver
{
    /**
     * Resolve the complete, ready-to-serve SEO payload for an entity. `$defaults` lets a caller
     * inject extra entity-derived defaults (lowest priority after branding, below the stored row).
     *
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public function resolve(SeoEntityType $type, string $key, array $defaults = []): array
    {
        $key = $type->isSingleton() ? $type->singletonKey() : $key;

        $meta = SeoMeta::query()
            ->where('entity_type', $type->value)
            ->where('entity_key', $key)
            ->first();

        // Cast attribute bag of the stored override (empty when there is no row). Reading via a plain
        // array keeps the merge simple and free of nullsafe-before-?? chains.
        $stored = $meta?->only([
            'meta_title', 'meta_description', 'keywords', 'canonical', 'robots_index', 'robots_follow',
            'og_title', 'og_description', 'og_image', 'twitter_title', 'twitter_description', 'twitter_image',
            'twitter_card', 'json_ld', 'breadcrumb', 'hreflang', 'sitemap_enabled', 'sitemap_priority', 'sitemap_changefreq',
        ]) ?? [];

        $derived = array_merge($this->brandingDefaults($type), $this->entityDefaults($type, $key), $defaults);

        $path = $type->path($key);

        // Canonical: prefer a valid stored canonical, else a valid injected default, else the entity URL.
        $canonical = $this->firstValidCanonical([
            is_string($stored['canonical'] ?? null) ? $stored['canonical'] : null,
            is_string($derived['canonical'] ?? null) ? $derived['canonical'] : null,
            $path,
        ]) ?? $path;

        // JSON-LD: emit the stored document only when valid, else the derived default when valid.
        $jsonLd = $this->firstValidJsonLd([$stored['json_ld'] ?? null, $derived['json_ld'] ?? null]);

        return [
            'entity_type' => $type->value,
            'entity_key' => $key,
            'meta_title' => $stored['meta_title'] ?? $derived['meta_title'] ?? null,
            'meta_description' => $stored['meta_description'] ?? $derived['meta_description'] ?? null,
            'keywords' => $stored['keywords'] ?? ($derived['keywords'] ?? null),
            'canonical' => $canonical,
            'robots_index' => $stored['robots_index'] ?? true,
            'robots_follow' => $stored['robots_follow'] ?? true,
            'og_title' => $stored['og_title'] ?? $stored['meta_title'] ?? $derived['meta_title'] ?? null,
            'og_description' => $stored['og_description'] ?? $stored['meta_description'] ?? $derived['meta_description'] ?? null,
            'og_image' => $stored['og_image'] ?? ($derived['og_image'] ?? null),
            'twitter_title' => $stored['twitter_title'] ?? $stored['meta_title'] ?? $derived['meta_title'] ?? null,
            'twitter_description' => $stored['twitter_description'] ?? $stored['meta_description'] ?? $derived['meta_description'] ?? null,
            'twitter_image' => $stored['twitter_image'] ?? $stored['og_image'] ?? ($derived['og_image'] ?? null),
            'twitter_card' => $stored['twitter_card'] ?? 'summary_large_image',
            'json_ld' => $jsonLd,
            'breadcrumb' => $stored['breadcrumb'] ?? ($derived['breadcrumb'] ?? null),
            'hreflang' => $stored['hreflang'] ?? null,
            'sitemap_enabled' => $stored['sitemap_enabled'] ?? true,
            'sitemap_priority' => $stored['sitemap_priority'] ?? null,
            'sitemap_changefreq' => $stored['sitemap_changefreq'] ?? null,
        ];
    }

    // ----- Defaults -----

    /**
     * Global fallbacks from branding/config used when neither the stored row nor the entity supplies
     * a value. Kept intentionally tiny — the frontend root layout owns the full brand chrome.
     *
     * @return array<string, mixed>
     */
    private function brandingDefaults(SeoEntityType $type): array
    {
        $name = (string) config('app.name', 'HElbaron');

        return [
            'meta_title' => ['en' => $name, 'ar' => $name],
        ];
    }

    /**
     * Sensible defaults derived from the underlying entity (a course's title/description, a category
     * name, a trainer name, an event title). Null-safe: a missing entity yields no defaults, and the
     * canonical simply falls back to the type's path. THE ONLY place the SEO module reads other
     * contexts' models.
     *
     * @return array<string, mixed>
     */
    private function entityDefaults(SeoEntityType $type, string $key): array
    {
        return match ($type) {
            SeoEntityType::Course => $this->courseDefaults($key),
            SeoEntityType::Category => $this->categoryDefaults($key),
            SeoEntityType::Trainer => $this->trainerDefaults($key),
            SeoEntityType::Event => $this->eventDefaults($key),
            default => [],
        };
    }

    /** @return array<string, mixed> */
    private function courseDefaults(string $key): array
    {
        // public_id is a uuid column — only compare it when $key is a uuid; slug is always safe.
        $course = Course::query()
            ->where(function ($q) use ($key): void {
                $q->where('slug', $key);
                if ($this->isUuid($key)) {
                    $q->orWhere('public_id', $key);
                }
            })
            ->first();
        if ($course === null) {
            return [];
        }

        $thumbnail = $course->getAttribute('thumbnail_path');

        return [
            'meta_title' => $this->bag($course->getAttribute('title')),
            'meta_description' => $this->bag($course->getAttribute('description') ?? $course->getAttribute('subtitle')),
            'og_image' => is_string($thumbnail) ? $thumbnail : null,
        ];
    }

    /** @return array<string, mixed> */
    private function categoryDefaults(string $key): array
    {
        $category = Category::query()
            ->where(function ($q) use ($key): void {
                $q->where('slug', $key);
                if ($this->isUuid($key)) {
                    $q->orWhere('public_id', $key);
                }
            })
            ->first();
        if ($category === null) {
            return [];
        }

        return [
            'meta_title' => $this->bag($category->getAttribute('name')),
            'meta_description' => $this->bag($category->getAttribute('description')),
        ];
    }

    /** @return array<string, mixed> */
    private function trainerDefaults(string $key): array
    {
        if (! $this->isUuid($key)) {
            return [];
        }

        $user = User::query()->where('public_id', $key)->first();
        if ($user === null) {
            return [];
        }

        return [
            'meta_title' => $this->bag($user->getAttribute('name')),
        ];
    }

    /** @return array<string, mixed> */
    private function eventDefaults(string $key): array
    {
        if (! $this->isUuid($key)) {
            return [];
        }

        $session = LiveSession::query()->where('public_id', $key)->first();
        if ($session === null) {
            return [];
        }

        return [
            'meta_title' => $this->bag($session->getAttribute('title')),
            'meta_description' => $this->bag($session->getAttribute('description')),
        ];
    }

    /** Whether $key is a UUID (public_id form) — guards uuid-typed column comparisons on pgsql. */
    private function isUuid(string $key): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $key) === 1;
    }

    /**
     * Normalize a value that may already be a bilingual bag or a plain string into a { en, ar } bag.
     *
     * @return array<string, string>|null
     */
    private function bag(mixed $value): ?array
    {
        if (is_array($value)) {
            $en = isset($value['en']) && is_string($value['en']) ? $value['en'] : '';
            $ar = isset($value['ar']) && is_string($value['ar']) ? $value['ar'] : $en;

            return $en === '' && $ar === '' ? null : ['en' => $en, 'ar' => $ar];
        }

        if (is_string($value) && $value !== '') {
            return ['en' => $value, 'ar' => $value];
        }

        return null;
    }

    // ----- Validation helpers (also reused by the admin Rules) -----

    /**
     * A canonical is valid when it is an absolute http(s) URL or a site-relative path ("/..."), and
     * never a dangerous scheme or a protocol-relative ("//") URL. Anchors/queries are permitted on a
     * relative path but a bare "#..." is not a canonical.
     */
    public function isValidCanonical(?string $url): bool
    {
        $value = trim((string) $url);
        if ($value === '') {
            return false;
        }

        $normalized = strtolower((string) preg_replace('/[\x00-\x20]+/', '', $value));
        foreach (['javascript', 'data', 'vbscript', 'file', 'blob'] as $scheme) {
            if (str_starts_with($normalized, $scheme.':')) {
                return false;
            }
        }

        if (preg_match('#^https?://#i', $value) === 1) {
            return true;
        }

        // Site-relative path only (reject protocol-relative "//host" and bare anchors).
        return str_starts_with($value, '/') && ! str_starts_with($value, '//');
    }

    /**
     * Valid JSON-LD is a decoded object/array, or a JSON string that decodes to one. Empty/scalar/
     * malformed input is invalid.
     */
    public function isValidJsonLd(mixed $json): bool
    {
        return $this->decodeJsonLd($json) !== null;
    }

    /**
     * Editorial warnings for the admin UI — non-blocking hints about a thin SEO record. Reports
     * missing meta title, meta description and social image (the fields that most affect SERP/social
     * previews).
     *
     * @return array<int, string>
     */
    public function warnings(SeoMeta $meta): array
    {
        $out = [];

        if ($this->emptyBag($meta->meta_title)) {
            $out[] = 'Missing meta title — a search-result title will be derived from the entity.';
        }
        if ($this->emptyBag($meta->meta_description)) {
            $out[] = 'Missing meta description — add one for a better search snippet.';
        }
        if (! is_string($meta->og_image) || trim($meta->og_image) === '') {
            $out[] = 'Missing social image (og:image) — shares will have no preview image.';
        }

        return $out;
    }

    // ----- Internals -----

    /**
     * @param  array<int, string|null>  $candidates
     */
    private function firstValidCanonical(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $this->isValidCanonical($candidate)) {
                return trim($candidate);
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $candidates
     * @return array<string, mixed>|array<int, mixed>|null
     */
    private function firstValidJsonLd(array $candidates): ?array
    {
        foreach ($candidates as $candidate) {
            $decoded = $this->decodeJsonLd($candidate);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|array<int, mixed>|null
     */
    private function decodeJsonLd(mixed $json): ?array
    {
        if (is_array($json)) {
            return $json === [] ? null : $json;
        }

        if (! is_string($json) || trim($json) === '') {
            return null;
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) && $decoded !== [] ? $decoded : null;
    }

    private function emptyBag(mixed $bag): bool
    {
        if (! is_array($bag)) {
            return true;
        }

        foreach ($bag as $value) {
            if (is_string($value) && trim($value) !== '') {
                return false;
            }
        }

        return true;
    }
}
