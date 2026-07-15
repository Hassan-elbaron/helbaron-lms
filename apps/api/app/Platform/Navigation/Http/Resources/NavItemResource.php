<?php

namespace App\Platform\Navigation\Http\Resources;

use App\Platform\Navigation\Models\NavItem;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * A single nav item as seen by the public API, with its children nested. Emits the bilingual label,
 * an always-SAFE url (never a raw admin string), the link target/rel, and the visibility metadata
 * the frontend uses to filter per visitor (roles / auth-state / locales / feature flag).
 *
 * Expects the `children` relation to be pre-populated (the controller assembles the tree in memory
 * from the flat, enabled, ordered item set).
 *
 * @property NavItem $resource
 */
class NavItemResource extends BaseResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var iterable<int, NavItem> $children */
        $children = $this->resource->getRelation('children') ?? [];

        return [
            'id' => $this->resource->public_id,
            'label' => $this->localized($this->resource->label),
            'url' => $this->resource->safeUrl(),
            'url_type' => $this->resource->url_type->value,
            'icon' => $this->resource->icon,
            'open_new_tab' => $this->resource->open_new_tab,
            'target' => $this->resource->open_new_tab ? '_blank' : '_self',
            'rel' => $this->resource->resolvedRel(),
            'badge' => $this->resource->badge !== null ? $this->localized($this->resource->badge) : null,
            'description' => $this->resource->description !== null ? $this->localized($this->resource->description) : null,
            'image' => $this->resource->image,
            'visibility' => [
                'roles' => $this->resource->visibility_roles,
                'auth' => $this->resource->visibility_auth->value,
                'locales' => $this->resource->visibility_locales,
                'feature_flag' => $this->resource->feature_flag,
            ],
            'children' => self::collection(collect($children)->values()),
        ];
    }

    /**
     * Normalise a bilingual bag to always carry both keys (so the frontend can pickLocale safely).
     *
     * @param  array<string, mixed>  $value
     * @return array{en: string, ar: string}
     */
    private function localized(array $value): array
    {
        $en = isset($value['en']) && is_string($value['en']) ? $value['en'] : '';
        $ar = isset($value['ar']) && is_string($value['ar']) ? $value['ar'] : '';

        return ['en' => $en, 'ar' => $ar !== '' ? $ar : $en];
    }
}
