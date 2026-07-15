<?php

namespace App\Platform\Navigation\Models;

use App\Platform\Navigation\Database\Factories\NavMenuFactory;
use App\Platform\Navigation\Enums\MenuLocation;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A navigation MENU LOCATION (mount point) — e.g. the public header or the learner sidebar. Menus
 * are seeded once (one per MenuLocation) and hold an ordered tree of NavItems. The public API reads
 * an active menu's enabled items for a location; the frontend keeps a hardcoded fallback so nav
 * never disappears.
 *
 * @property int $id
 * @property string $public_id
 * @property MenuLocation $location
 * @property bool $is_active
 */
class NavMenu extends Model
{
    /** @use HasFactory<NavMenuFactory> */
    use HasFactory;

    use HasPublicId;

    protected $fillable = ['location', 'is_active'];

    protected function casts(): array
    {
        return [
            'location' => MenuLocation::class,
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): NavMenuFactory
    {
        return NavMenuFactory::new();
    }

    /**
     * All items in this menu, ordered for tree assembly (parents before position).
     *
     * @return HasMany<NavItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(NavItem::class, 'menu_id')->orderBy('position');
    }

    /**
     * Active menus only.
     *
     * @param  Builder<NavMenu>  $query
     * @return Builder<NavMenu>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Restrict to a single location.
     *
     * @param  Builder<NavMenu>  $query
     * @return Builder<NavMenu>
     */
    public function scopeForLocation(Builder $query, MenuLocation|string $location): Builder
    {
        $value = $location instanceof MenuLocation ? $location->value : $location;

        return $query->where('location', $value);
    }
}
