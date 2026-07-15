<?php

namespace App\Platform\Branding\Database\Seeders;

use App\Platform\Branding\Models\BrandSetting;
use Illuminate\Database\Seeder;

/**
 * Seeds the single brand_settings row. Idempotent: firstOrCreate([]) creates the row once and does
 * nothing on subsequent runs. The row is intentionally created "empty" — every value is supplied by
 * BrandSetting::defaults() (brand HElbaron, the current globals.css OKLCH theme, SAR / en /
 * Asia/Riyadh), so the site renders on-brand out of the box and admins override only what they want.
 */
class BrandingSeeder extends Seeder
{
    public function run(): void
    {
        BrandSetting::firstOrCreate([]);
    }
}
