<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Centralized per-entity SEO OVERRIDE store (see App\Platform\Seo). One row per addressable
        // surface, keyed by (entity_type, entity_key). Every column is an OPTIONAL override merged by
        // the SeoResolver over entity-derived + global-branding defaults — an absent/null field means
        // "use the default". Bilingual fields ({ en, ar }) are JSON bags.
        Schema::create('seo_metas', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->string('entity_type', 32);
            $table->string('entity_key');
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->string('keywords', 500)->nullable();
            $table->string('canonical', 2048)->nullable();
            $table->boolean('robots_index')->default(true);
            $table->boolean('robots_follow')->default(true);
            $table->json('og_title')->nullable();
            $table->json('og_description')->nullable();
            $table->string('og_image', 2048)->nullable();
            $table->json('twitter_title')->nullable();
            $table->json('twitter_description')->nullable();
            $table->string('twitter_image', 2048)->nullable();
            $table->string('twitter_card', 32)->default('summary_large_image');
            $table->json('json_ld')->nullable();
            $table->json('breadcrumb')->nullable();
            $table->json('hreflang')->nullable();
            $table->boolean('sitemap_enabled')->default(true);
            $table->decimal('sitemap_priority', 2, 1)->nullable();
            $table->string('sitemap_changefreq', 16)->nullable();
            $table->timestamps();

            $table->unique(['entity_type', 'entity_key']);
            $table->index(['sitemap_enabled', 'robots_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_metas');
    }
};
