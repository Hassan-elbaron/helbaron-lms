<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `courses.is_free` — freeness becomes a STATED INTENT instead of an inference.
 *
 * Freeness used to be derived from the absence of a product row, which made a single
 * `product_courses` row a one-way door that two ordinary admin actions walked through:
 *
 *   - building an "All Access" bundle that includes a free intro course permanently un-freed that
 *     course, and
 *   - a draft product created by mistake, or an abandoned pricing experiment, did the same forever.
 *
 * The documented escape hatch was deleting the product, which the admin panel offers no way to do
 * (ProductResource exposes only CreateAction — no Delete, Restore or Trashed filter), so recovery
 * required tinker or a DBA. An inference cannot express "this course is free even though a bundle
 * also grants it"; a column can.
 *
 * BACKFILL. Existing rows are set to `is_free = true` exactly where no product_courses row grants the
 * course — reproducing today's inferred answer — so behaviour is unchanged the moment this runs. It
 * derives entirely from existing data and destroys nothing.
 *
 * The default for NEW courses is false, which is fail-closed: a course is not giveable-away until
 * somebody says so. CourseReadinessService warns (never blocks) when a published course is neither
 * free nor sold, so an author cannot silently ship something nobody can enrol in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->boolean('is_free')->default(false)->after('visibility');
        });

        // Preserve the current inferred behaviour for everything that already exists.
        DB::table('courses')
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('product_courses')
                    ->whereColumn('product_courses.course_id', 'courses.id');
            })
            ->update(['is_free' => true]);
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->dropColumn('is_free');
        });
    }
};
