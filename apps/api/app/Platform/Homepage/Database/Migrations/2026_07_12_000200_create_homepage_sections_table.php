<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Predefined homepage blocks (see App\Platform\Homepage\Enums\BlockType). One row per
        // block `key`. `content` is the working/draft bilingual copy; `published_content` is the
        // live snapshot taken by publish() and served to the public homepage.
        Schema::create('homepage_sections', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->string('key')->unique();
            $table->string('type', 32);
            $table->integer('position')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->json('content');
            $table->json('published_content')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['is_enabled', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_sections');
    }
};
