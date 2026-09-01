<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every slug a record has ever had, so renaming one does not break the web.
 *
 * A course slug is the primary public URL. Renaming a course previously 404'd every inbound link,
 * every indexed search result and every share — silently, and with no way back, because the old
 * string was simply overwritten. This table keeps it.
 *
 * TWO JOBS, and the second one is the subtle half:
 *
 * 1. LOOKUP. A request for a retired slug still resolves, and the caller can send a permanent
 *    redirect to the canonical one.
 * 2. UNIQUENESS. A retired slug must never be handed to a DIFFERENT record. If course A is renamed
 *    from `python-basics` and course B is then allowed to take that slug, the redirect for A now
 *    points at B: every historic link, backlink and search result for A quietly lands on somebody
 *    else's course. That is worse than a 404, so `HasSlug` checks candidate slugs against the live
 *    column AND this table.
 *
 * Shared, not per-domain: eight models use HasSlug and the guarantee has to be the same for all of
 * them. `(sluggable_type, slug)` is unique for that reason — history is scoped per model type, so
 * a category and a course may each have owned `design`, but two courses may not.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slug_history', function (Blueprint $table): void {
            $table->id();
            $table->string('sluggable_type');
            $table->unsignedBigInteger('sluggable_id');
            $table->string('slug');
            $table->timestamp('created_at')->nullable();

            // Job 2: the slug is claimed for this model type and cannot be reissued.
            $table->unique(['sluggable_type', 'slug']);
            // Job 1: resolve a retired slug on the public read path.
            $table->index(['sluggable_type', 'sluggable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slug_history');
    }
};
