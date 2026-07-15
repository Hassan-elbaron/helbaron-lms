<?php

namespace App\Platform\Branding\Database\Factories;

use App\Platform\Branding\Models\BrandSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BrandSetting>
 */
class BrandSettingFactory extends Factory
{
    protected $model = BrandSetting::class;

    /** By default a factory row stores nothing — the model's defaults() supply every value. */
    public function definition(): array
    {
        return [
            'identity' => null,
            'logos' => null,
            'theme' => null,
            'email' => null,
            'certificate' => null,
        ];
    }

    /** Store a custom theme colour (deep-merged over defaults by toPublicArray). */
    public function primaryColor(string $color): static
    {
        return $this->state(fn () => ['theme' => ['colors' => ['primary' => $color]]]);
    }

    /** Store a custom brand name. */
    public function brandName(string $en, string $ar): static
    {
        return $this->state(fn () => ['identity' => ['brand_name' => ['en' => $en, 'ar' => $ar]]]);
    }
}
