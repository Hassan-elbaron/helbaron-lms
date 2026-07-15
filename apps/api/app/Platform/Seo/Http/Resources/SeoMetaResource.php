<?php

namespace App\Platform\Seo\Http\Resources;

use App\Platform\Seo\Models\SeoMeta;
use App\Platform\Seo\Services\SeoResolver;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * A managed SEO record as seen by the admin manager list (GET /api/v1/seo). Emits the stored
 * override fields plus the derived public path and non-blocking editorial warnings (missing
 * title/description/image) so the manager can flag thin records without re-deriving SEO logic.
 *
 * @property SeoMeta $resource
 */
class SeoMetaResource extends BaseResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $meta = $this->resource;

        return [
            'id' => $meta->public_id,
            'entity_type' => $meta->entity_type->value,
            'entity_key' => $meta->entity_key,
            'path' => $meta->entity_type->path($meta->entity_key),
            'meta_title' => $meta->meta_title,
            'meta_description' => $meta->meta_description,
            'canonical' => $meta->canonical,
            'robots_index' => $meta->robots_index,
            'robots_follow' => $meta->robots_follow,
            'og_image' => $meta->og_image,
            'sitemap_enabled' => $meta->sitemap_enabled,
            'sitemap_priority' => $meta->sitemap_priority,
            'sitemap_changefreq' => $meta->sitemap_changefreq,
            'warnings' => app(SeoResolver::class)->warnings($meta),
            'updated_at' => $meta->updated_at?->toIso8601String(),
        ];
    }
}
