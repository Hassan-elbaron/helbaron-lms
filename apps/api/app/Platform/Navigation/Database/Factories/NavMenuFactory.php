<?php

namespace App\Platform\Navigation\Database\Factories;

use App\Platform\Navigation\Enums\MenuLocation;
use App\Platform\Navigation\Models\NavMenu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NavMenu>
 */
class NavMenuFactory extends Factory
{
    protected $model = NavMenu::class;

    public function definition(): array
    {
        return [
            'location' => MenuLocation::PublicHeader,
            'is_active' => true,
        ];
    }

    public function location(MenuLocation $location): static
    {
        return $this->state(fn () => ['location' => $location]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
