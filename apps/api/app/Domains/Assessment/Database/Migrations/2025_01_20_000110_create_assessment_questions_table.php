<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per question, discriminated by `type` (QuestionType).
 *
 * Everything type-specific goes in `config` (json) — never in a new column. Examples of what a
 * future type stores there without touching this migration:
 *   fill_in_blank : {"blanks": 2, "case_sensitive": false}
 *   numeric       : {"tolerance": 0.01, "unit": "kg"}
 *   essay         : {"min_words": 150, "rubric_id": "..."}
 *   code          : {"language": "python", "tests": [...]}
 *   matching      : {"pair_mode": "one_to_one"}
 * Choices and accepted answers live in `assessment_question_options`, which is generic enough to
 * carry both. That is the whole reason no new type needs a migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_questions', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();

            $table->string('type', 32);                 // QuestionType discriminator
            $table->text('prompt');                     // sanitized HTML
            $table->json('config')->nullable();         // type-specific settings

            $table->text('explanation')->nullable();    // shown per FeedbackMode
            $table->text('hint')->nullable();           // shown during the attempt

            // Decimal, not integer: half marks and weighted questions are common in real exams.
            $table->decimal('points', 8, 2)->default(1);
            // Penalty applied when wrong, only if the assessment enables negative_marking.
            $table->decimal('negative_points', 8, 2)->default(0);

            $table->string('difficulty', 16)->nullable();   // Difficulty
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['assessment_id', 'position']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_questions');
    }
};
