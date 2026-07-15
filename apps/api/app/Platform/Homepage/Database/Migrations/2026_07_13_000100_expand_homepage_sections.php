<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Additive expansion of the predefined-block homepage CMS (see App\Platform\Homepage). All
        // columns are nullable or defaulted so existing rows keep rendering exactly as before. The
        // editorial `status` defaults to 'published' to preserve current public behavior; a backfill
        // below stamps any pre-existing rows explicitly.
        Schema::table('homepage_sections', function (Blueprint $table): void {
            $table->string('status', 16)->default('published')->after('is_enabled');
            $table->timestamp('unpublished_at')->nullable()->after('published_at');

            // Presentation metadata — all optional; the frontend renders dynamically from these.
            $table->string('layout_variant', 48)->nullable();
            $table->string('spacing', 24)->nullable();
            $table->string('alignment', 24)->nullable();
            $table->string('container_width', 24)->nullable();
            $table->string('animation', 48)->nullable();
            $table->string('theme_variant', 48)->nullable();
            $table->json('background')->nullable();          // { color, image, video, overlay }
            $table->json('accessibility_label')->nullable(); // { en, ar }

            // Responsive device visibility (defaults keep blocks visible everywhere).
            $table->boolean('visible_desktop')->default(true);
            $table->boolean('visible_tablet')->default(true);
            $table->boolean('visible_mobile')->default(true);

            $table->index(['status', 'published_at']);
        });

        // Explicitly stamp any pre-existing rows to Published so the schedule-aware published() scope
        // includes them (the column default already covers this, but be deterministic on backfill).
        DB::table('homepage_sections')->update(['status' => 'published']);

        // Append-only version history: one snapshot of a block's fields per recorded version, used
        // for the admin version list and rollback. Mirrors static_page_versions. Snapshots are taken
        // on every block update.
        Schema::create('homepage_section_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('homepage_section_id')->constrained('homepage_sections')->cascadeOnDelete();
            $table->integer('version');
            $table->json('snapshot');
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['homepage_section_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_section_versions');

        Schema::table('homepage_sections', function (Blueprint $table): void {
            $table->dropIndex(['status', 'published_at']);
            $table->dropColumn([
                'status', 'unpublished_at', 'layout_variant', 'spacing', 'alignment',
                'container_width', 'animation', 'theme_variant', 'background', 'accessibility_label',
                'visible_desktop', 'visible_tablet', 'visible_mobile',
            ]);
        });
    }
};
