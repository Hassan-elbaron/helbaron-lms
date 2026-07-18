<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An Assessment is an independent, reusable entity. It is NOT owned by a lesson — a lesson merely
 * references one (nullable `lessons.assessment_id`). That is what allows the same assessment to be
 * reused across lessons, promoted to a course-level final, or kept as a bank template later
 * without a schema redesign.
 *
 * `course_id` is the AUTHORIZATION anchor: an instructor may manage an assessment because they
 * train its course. It is nullable so a future org-level shared bank (course_id = null, managed by
 * admins) needs no migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->publicId();

            // Ownership / authorization anchor. Null = platform-level bank (admin-managed).
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->string('scope', 24)->default('lesson');   // AssessmentScope
            $table->string('status', 16)->default('draft');   // AssessmentStatus

            // ── Scoring ────────────────────────────────────────────────────
            // Percentage (0-100) the learner must reach. Null = ungraded / practice.
            $table->unsignedTinyInteger('passing_score')->nullable();
            // Global switch; per-question penalties live on assessment_questions.negative_points.
            $table->boolean('negative_marking')->default(false);

            // ── Attempt rules ──────────────────────────────────────────────
            $table->unsignedSmallInteger('max_attempts')->nullable();      // null = unlimited
            $table->unsignedInteger('time_limit_seconds')->nullable();     // null = untimed
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_options')->default(false);
            // Random subset per attempt (question-bank behaviour). Null = serve every question.
            $table->unsignedSmallInteger('questions_per_attempt')->nullable();

            $table->string('feedback_mode', 16)->default('after_submit');  // FeedbackMode

            // ── Versioning-ready ───────────────────────────────────────────
            // Published assessments are immutable in spirit: editing one creates the next version
            // and points `parent_assessment_id` at the original, so historical attempts keep
            // referencing the exact version that was sat. V1 stores the lineage; the promotion
            // action arrives with the versioning step.
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('parent_assessment_id')->nullable()
                ->constrained('assessments')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['course_id', 'status']);
            $table->index(['scope', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
