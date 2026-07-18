<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One learner response per question per attempt.
 *
 * `response` is json precisely so no future question type needs a column:
 *   single_choice / true_false → {"option_ids": ["<uuid>"]}
 *   multiple_choice           → {"option_ids": ["<uuid>", "<uuid>"]}
 *   short_answer              → {"text": "photosynthesis"}
 *   fill_in_blank             → {"blanks": {"0": "carbon", "1": "dioxide"}}
 *   (later) ordering          → {"sequence": ["<uuid>", ...]}
 *   (later) essay             → {"text": "..."}  graded by grader_id rather than the engine
 *
 * `is_correct` is nullable on purpose: null means "not yet graded", which is the state a manually
 * graded answer sits in. `grader_id` and `graded_at` exist now so adding Essay requires no
 * migration — only a new grader that declines to auto-score.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_answers', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('attempt_id')->constrained('assessment_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('assessment_questions')->cascadeOnDelete();

            $table->json('response')->nullable();          // null = seen but unanswered

            $table->boolean('is_correct')->nullable();     // null = pending grading
            $table->decimal('points_awarded', 8, 2)->nullable();

            $table->timestamp('graded_at')->nullable();
            $table->foreignId('grader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('feedback')->nullable();          // grader note (manual types)

            $table->timestamps();

            // One answer row per question per attempt — saving is an upsert, not an append.
            $table->unique(['attempt_id', 'question_id'], 'assessment_answer_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_answers');
    }
};
