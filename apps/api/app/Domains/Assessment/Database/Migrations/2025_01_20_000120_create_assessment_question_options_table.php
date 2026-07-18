<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Generic answer-key row. Its meaning is decided by the parent question's `type`:
 *
 *   single_choice / multiple_choice / true_false → a selectable choice; `is_correct` marks the key
 *   short_answer                                 → an ACCEPTED answer string in `value`
 *   fill_in_blank                                → an accepted string for blank number `group_index`
 *
 * Reserved for later types with no migration required:
 *   matching  → `group_index` pairs left/right members
 *   ordering  → `position` IS the correct sequence
 *   numeric   → `value` holds the target, tolerance lives in the question's `config`
 *
 * `group_index` is what makes multi-part questions (blanks, pairs) possible without a third table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_question_options', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('question_id')->constrained('assessment_questions')->cascadeOnDelete();

            // Learner-visible text (choices). Null for pure accepted-answer rows, which use `value`.
            $table->text('label')->nullable();
            // Machine-comparable value: accepted answer text, numeric target, matching key.
            $table->string('value', 512)->nullable();

            $table->boolean('is_correct')->default(false);
            // Sub-part index: which blank / which matching pair. 0 for single-part questions.
            $table->unsignedSmallInteger('group_index')->default(0);

            $table->text('feedback')->nullable();       // per-choice explanation
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->index(['question_id', 'position']);
            $table->index(['question_id', 'group_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_question_options');
    }
};
