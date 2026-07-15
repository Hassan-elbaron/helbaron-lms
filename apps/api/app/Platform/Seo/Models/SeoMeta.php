<?php

namespace App\Platform\Seo\Models;

use App\Platform\Seo\Database\Factories\SeoMetaFactory;
use App\Platform\Seo\Enums\SeoEntityType;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A per-entity SEO OVERRIDE record for the centralized SEO Manager. One row per addressable surface,
 * uniquely keyed by (entity_type, entity_key). Every field is an optional override: the SeoResolver
 * merges the non-null fields here OVER entity-derived defaults and global branding defaults, so a
 * row never needs to be "complete". Bilingual fields are stored as { en, ar } JSON bags.
 *
 * This model holds NO metadata-generation logic — merging/validation lives in SeoResolver so the
 * store stays a plain override table with a single resolver as its read path.
 *
 * @property int $id
 * @property string $public_id
 * @property SeoEntityType $entity_type
 * @property string $entity_key
 * @property array<string, string>|null $meta_title
 * @property array<string, string>|null $meta_description
 * @property string|null $keywords
 * @property string|null $canonical
 * @property bool $robots_index
 * @property bool $robots_follow
 * @property array<string, string>|null $og_title
 * @property array<string, string>|null $og_description
 * @property string|null $og_image
 * @property array<string, string>|null $twitter_title
 * @property array<string, string>|null $twitter_description
 * @property string|null $twitter_image
 * @property string $twitter_card
 * @property array<string, mixed>|null $json_ld
 * @property array<int, mixed>|null $breadcrumb
 * @property array<string, string>|null $hreflang
 * @property bool $sitemap_enabled
 * @property float|null $sitemap_priority
 * @property string|null $sitemap_changefreq
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SeoMeta extends Model
{
    /** @use HasFactory<SeoMetaFactory> */
    use HasFactory;

    use HasPublicId;

    protected $fillable = [
        'entity_type', 'entity_key', 'meta_title', 'meta_description', 'keywords', 'canonical',
        'robots_index', 'robots_follow', 'og_title', 'og_description', 'og_image',
        'twitter_title', 'twitter_description', 'twitter_image', 'twitter_card',
        'json_ld', 'breadcrumb', 'hreflang', 'sitemap_enabled', 'sitemap_priority', 'sitemap_changefreq',
    ];

    protected function casts(): array
    {
        return [
            'entity_type' => SeoEntityType::class,
            'meta_title' => 'array',
            'meta_description' => 'array',
            'og_title' => 'array',
            'og_description' => 'array',
            'twitter_title' => 'array',
            'twitter_description' => 'array',
            'json_ld' => 'array',
            'breadcrumb' => 'array',
            'hreflang' => 'array',
            'robots_index' => 'boolean',
            'robots_follow' => 'boolean',
            'sitemap_enabled' => 'boolean',
            'sitemap_priority' => 'float',
        ];
    }

    protected static function newFactory(): SeoMetaFactory
    {
        return SeoMetaFactory::new();
    }
}
