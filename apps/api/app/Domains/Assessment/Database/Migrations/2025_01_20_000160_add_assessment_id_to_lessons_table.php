<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A lesson REFERENCES an assessment; it does not own one. Deliberately a nullable FK rather than a
 * key inside `lessons.content`:
 *
 *   • the assessment stays reusable and independently versioned;
 *   • deleting an assessment nulls the reference instead of corrupting a JSON blob;
 *   • the relationship is queryable and enforceable by the database.
 *
 * V1 is at most one assessment per lesson. Many-to-many (shared assessments, multiple quizzes per
 * lesson) is an additive pivot table later — this column becomes the migration source, and no
 * existing data has to move.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->foreignId('assessment_id')->nullable()->after('content')
                ->constrained('assessments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assessment_id');
        });
    }
};
