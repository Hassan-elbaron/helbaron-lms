<?php

namespace App\Platform\Homepage\Http\Resources;

use App\Platform\Homepage\Models\HomepageSection;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * A single homepage block as seen by the public/preview API. Emits the block identity, its resolved
 * bilingual content (published snapshot for the live endpoint, working draft for the builder
 * preview), the presentation metadata the frontend renders dynamically (layout / spacing / alignment
 * / container / animation / theme / background / accessibility / device visibility) and, for blocks
 * that reference domain entities, their server-resolved `resolved` payload.
 *
 * @property HomepageSection $resource
 */
class HomepageSectionResource extends BaseResource
{
    /**
     * @param  array<string, mixed>|null  $resolved  server-resolved referenced entities (or null)
     */
    public function __construct(
        HomepageSection $resource,
        private readonly bool $draft = false,
        private readonly ?array $resolved = null,
    ) {
        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->resource->key,
            'type' => $this->resource->type->value,
            'position' => $this->resource->position,
            'content' => $this->draft ? ($this->resource->content ?? []) : $this->resource->resolvedContent(),
            'resolved' => $this->resolved,
            'presentation' => [
                'layout_variant' => $this->resource->layout_variant,
                'spacing' => $this->resource->spacing,
                'alignment' => $this->resource->alignment,
                'container_width' => $this->resource->container_width,
                'animation' => $this->resource->animation,
                'theme_variant' => $this->resource->theme_variant,
                'background' => $this->resource->background,
            ],
            'accessibility_label' => $this->resource->accessibility_label,
            'visibility' => [
                'desktop' => $this->resource->visible_desktop,
                'tablet' => $this->resource->visible_tablet,
                'mobile' => $this->resource->visible_mobile,
            ],
        ];
    }
}
