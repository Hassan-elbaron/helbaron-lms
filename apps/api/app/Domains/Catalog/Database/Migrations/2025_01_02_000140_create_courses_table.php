<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('level_id')->nullable()->constrained('course_levels')->nullOnDelete();
            $table->foreignId('language_id')->nullable()->constrained('course_languages')->nullOnDelete();
            $table->string('status', 16)->default('draft');
            $table->string('visibility', 16)->default('public');
            $table->boolean('is_featured')->default(false);
            $table->string('thumbnail_path')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->seoColumns();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'visibility']);
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
