<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Normalised labels for questions and assessments. One table with a `kind` discriminator carries
 * BOTH free-form tags and learning objectives — they have identical storage needs and differ only
 * in how they are surfaced. Keeping them normalised (rather than a json array on the question) is
 * what makes future question banks able to assemble an attempt by objective coverage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_tags', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->string('kind', 16)->default('tag');   // 'tag' | 'objective'
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['kind', 'slug']);
        });

        // Polymorphic so an objective can be attached to a question today and to a whole
        // assessment, a course, or a bank later — without another pivot table.
        Schema::create('assessment_taggables', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained('assessment_tags')->cascadeOnDelete();
            $table->morphs('taggable');

            $table->primary(['tag_id', 'taggable_id', 'taggable_type'], 'assessment_taggable_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_taggables');
        Schema::dropIfExists('assessment_tags');
    }
};
