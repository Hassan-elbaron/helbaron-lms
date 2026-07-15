<?php

namespace App\Platform\Navigation\Models;

use App\Platform\Navigation\Database\Factories\NavItemFactory;
use App\Platform\Navigation\Enums\NavAuthVisibility;
use App\Platform\Navigation\Enums\NavUrlType;
use App\Platform\Navigation\Support\NavUrl;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A single admin-editable navigation link. Belongs to a NavMenu (location) and forms a tree via
 * parent_id. Labels/badges/descriptions are bilingual JSON ({ en, ar }). URLs are always run
 * through NavUrl (safeUrl accessor) so an unsafe href can never be rendered even if one slipped
 * past write-time validation. Per-item visibility (roles / auth-state / locales / feature flag) is
 * carried to the API and applied client-side for the current visitor.
 *
 * @property int $id
 * @property string $public_id
 * @property int $menu_id
 * @property int|null $parent_id
 * @property array<string, string> $label
 * @property NavUrlType $url_type
 * @property string $url
 * @property string|null $icon
 * @property int $position
 * @property bool $is_enabled
 * @property bool $open_new_tab
 * @property string|null $rel
 * @property array<string, string>|null $badge
 * @property array<string, string>|null $description
 * @property string|null $image
 * @property array<int, string>|null $visibility_roles
 * @property NavAuthVisibility $visibility_auth
 * @property array<int, string>|null $visibility_locales
 * @property string|null $feature_flag
 */
class NavItem extends Model
{
    /** @use HasFactory<NavItemFactory> */
    use HasFactory;

    use HasPublicId;
    use SoftDeletes;

    protected $fillable = [
        'menu_id', 'parent_id', 'label', 'url_type', 'url', 'icon', 'position', 'is_enabled',
        'open_new_tab', 'rel', 'badge', 'description', 'image', 'visibility_roles',
        'visibility_auth', 'visibility_locales', 'feature_flag',
    ];

    protected function casts(): array
    {
        return [
            'label' => 'array',
            'url_type' => NavUrlType::class,
            'position' => 'integer',
            'is_enabled' => 'boolean',
            'open_new_tab' => 'boolean',
            'badge' => 'array',
            'description' => 'array',
            'visibility_roles' => 'array',
            'visibility_auth' => NavAuthVisibility::class,
            'visibility_locales' => 'array',
        ];
    }

    protected static function newFactory(): NavItemFactory
    {
        return NavItemFactory::new();
    }

    /** @return BelongsTo<NavMenu, $this> */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(NavMenu::class, 'menu_id');
    }

    /** @return BelongsTo<NavItem, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(NavItem::class, 'parent_id');
    }

    /** @return HasMany<NavItem, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(NavItem::class, 'parent_id')->orderBy('position');
    }

    /**
     * Enabled items in render order.
     *
     * @param  Builder<NavItem>  $query
     * @return Builder<NavItem>
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true)->orderBy('position');
    }

    /**
     * The always-safe href for this item — the stored URL when it passes the safety rules,
     * otherwise "#". No caller should read `url` directly for rendering.
     */
    public function safeUrl(): string
    {
        return NavUrl::sanitize($this->url_type, $this->url);
    }

    /**
     * The resolved rel attribute. Ensures noopener/noreferrer are present whenever the link opens
     * in a new tab or is external — combined with any admin-supplied rel tokens.
     */
    public function resolvedRel(): ?string
    {
        $tokens = array_filter(preg_split('/\s+/', (string) $this->rel) ?: []);

        if ($this->open_new_tab || $this->url_type === NavUrlType::External) {
            $tokens[] = 'noopener';
            $tokens[] = 'noreferrer';
        }

        $tokens = array_values(array_unique($tokens));

        return $tokens === [] ? null : implode(' ', $tokens);
    }
}
