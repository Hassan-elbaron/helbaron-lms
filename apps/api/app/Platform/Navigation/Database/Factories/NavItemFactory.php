<?php

namespace App\Platform\Navigation\Database\Factories;

use App\Platform\Navigation\Enums\NavAuthVisibility;
use App\Platform\Navigation\Enums\NavUrlType;
use App\Platform\Navigation\Models\NavItem;
use App\Platform\Navigation\Models\NavMenu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NavItem>
 */
class NavItemFactory extends Factory
{
    protected $model = NavItem::class;

    public function definition(): array
    {
        $label = $this->faker->unique()->words(2, true);

        return [
            'menu_id' => NavMenu::factory(),
            'parent_id' => null,
            'label' => ['en' => ucfirst($label), 'ar' => ucfirst($label)],
            'url_type' => NavUrlType::Internal,
            'url' => '/'.$this->faker->slug(),
            'icon' => null,
            'position' => $this->faker->numberBetween(0, 100),
            'is_enabled' => true,
            'open_new_tab' => false,
            'rel' => null,
            'badge' => null,
            'description' => null,
            'image' => null,
            'visibility_roles' => null,
            'visibility_auth' => NavAuthVisibility::Any,
            'visibility_locales' => null,
            'feature_flag' => null,
        ];
    }

    public function forMenu(NavMenu $menu): static
    {
        return $this->state(fn () => ['menu_id' => $menu->id]);
    }

    public function childOf(NavItem $parent): static
    {
        return $this->state(fn () => ['menu_id' => $parent->menu_id, 'parent_id' => $parent->id]);
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['is_enabled' => false]);
    }

    public function external(string $url = 'https://example.com'): static
    {
        return $this->state(fn () => ['url_type' => NavUrlType::External, 'url' => $url, 'open_new_tab' => true]);
    }
}
