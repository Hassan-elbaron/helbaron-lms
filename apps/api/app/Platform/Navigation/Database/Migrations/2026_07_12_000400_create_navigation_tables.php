<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Menu locations (mount points). One row per MenuLocation the site can render; items hang
        // off these. `location` is unique (a location maps to exactly one menu).
        Schema::create('nav_menus', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->string('location', 64)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Navigation items — the admin-editable links. Self-referencing tree via parent_id; ordered
        // within a (menu, parent) by position. Bilingual label/badge/description JSON. Per-item
        // visibility metadata (roles / auth-state / locales / feature flag) is emitted to the API so
        // the frontend can filter for the current visitor.
        Schema::create('nav_items', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('menu_id')->constrained('nav_menus')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('nav_items')->nullOnDelete();
            $table->json('label');
            $table->string('url_type', 16)->default('internal');
            $table->string('url')->default('#');
            $table->string('icon')->nullable();
            $table->integer('position')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('open_new_tab')->default(false);
            $table->string('rel')->nullable();
            $table->json('badge')->nullable();
            $table->json('description')->nullable();
            $table->string('image')->nullable();
            $table->json('visibility_roles')->nullable();
            $table->string('visibility_auth', 16)->default('any');
            $table->json('visibility_locales')->nullable();
            $table->string('feature_flag')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['menu_id', 'parent_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nav_items');
        Schema::dropIfExists('nav_menus');
    }
};
