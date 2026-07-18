<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One learner sitting of one assessment.
 *
 * `question_order` freezes the randomised selection and ordering at start time. Without it, a
 * shuffled or subset-based attempt would be non-reproducible: reloading the page would serve
 * different questions, and grading could not be audited afterwards. This is also what makes
 * `questions_per_attempt` (question-bank behaviour) safe.
 *
 * `assessment_version` records which version was sat, so editing an assessment later never
 * retroactively changes what a learner was asked.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_attempts', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Where the attempt was taken from. Null for a direct/standalone sitting.
            $table->foreignId('lesson_id')->nullable()->constrained('lessons')->nullOnDelete();

            $table->unsignedSmallInteger('attempt_number');
            $table->unsignedInteger('assessment_version')->default(1);
            $table->string('status', 24)->default('in_progress');   // AttemptStatus

            $table->timestamp('started_at');
            $table->timestamp('expires_at')->nullable();     // set when the assessment is timed
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('graded_at')->nullable();

            $table->decimal('score', 8, 2)->nullable();       // points awarded
            $table->decimal('max_score', 8, 2)->nullable();   // points available in THIS attempt
            $table->decimal('percentage', 5, 2)->nullable();
            $table->boolean('passed')->nullable();            // null = not graded / ungraded assessment

            // Frozen list of question ids, in serve order. Source of truth for this sitting.
            $table->json('question_order')->nullable();

            $table->timestamps();

            $table->unique(['assessment_id', 'user_id', 'attempt_number'], 'assessment_attempt_unique');
            $table->index(['user_id', 'status']);
            $table->index(['assessment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_attempts');
    }
};
