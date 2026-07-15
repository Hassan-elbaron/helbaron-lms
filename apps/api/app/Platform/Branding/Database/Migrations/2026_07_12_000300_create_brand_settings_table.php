<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Single-row white-label / branding settings (see App\Platform\Branding\Models\BrandSetting).
        // Every configurable surface is grouped into a nullable JSON bag; unset values fall back to
        // the model's built-in defaults() (which mirror the current globals.css design tokens), so
        // the public site is never broken by empty branding. Presentation-only — nothing sensitive.
        Schema::create('brand_settings', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->json('identity')->nullable();    // brand/company/copyright/support/social/locale
            $table->json('logos')->nullable();       // logo/favicon/icons/loader/login background paths
            $table->json('theme')->nullable();        // colors/radius/fonts/spacing/dark overrides/preset
            $table->json('email')->nullable();        // email header/footer/colors/signature branding
            $table->json('certificate')->nullable();  // certificate background/logo/typography/margins
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_settings');
    }
};
