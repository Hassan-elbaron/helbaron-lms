<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Structured static CMS pages (see App\Platform\Pages). One row per page, addressed by a
        // unique `slug`. `title`/`body`/`excerpt`/`seo` are bilingual/structured JSON bags. Only a
        // `published` row inside its published_at/unpublished_at window is served publicly.
        Schema::create('static_pages', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->string('slug')->unique();
            $table->string('template', 32)->default('standard');
            $table->json('title');
            $table->json('body');
            $table->json('excerpt')->nullable();
            $table->string('hero_image', 2048)->nullable();
            $table->string('status', 16)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('unpublished_at')->nullable();
            $table->integer('position')->default(0);
            $table->boolean('show_in_nav')->default(false);
            $table->json('seo')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['show_in_nav', 'position']);
        });

        // Append-only version history: one snapshot of the page fields per recorded version, used
        // for the admin version list and rollback. Snapshots are taken on every page update.
        Schema::create('static_page_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('static_page_id')->constrained('static_pages')->cascadeOnDelete();
            $table->integer('version');
            $table->json('snapshot');
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['static_page_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('static_page_versions');
        Schema::dropIfExists('static_pages');
    }
};
