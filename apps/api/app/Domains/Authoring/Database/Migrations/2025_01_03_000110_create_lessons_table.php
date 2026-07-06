<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('section_id')->constrained('course_sections')->cascadeOnDelete();
            $table->string('title');
            $table->string('type', 24);
            $table->json('content')->nullable(); // article body / external_url / notes (no playback)
            $table->unsignedInteger('position')->default(0);
            $table->string('publish_state', 16)->default('draft');
            $table->boolean('is_preview')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['section_id', 'position']);
            $table->index('publish_state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
