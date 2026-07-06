<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Media METADATA only. Playback/signing belongs to Learning, not Authoring.
        Schema::create('lesson_media', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('lesson_id')->unique()->constrained('lessons')->cascadeOnDelete();
            $table->string('mux_asset_id')->nullable();
            $table->string('mux_playback_id')->nullable();
            $table->string('s3_key')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('duration')->nullable();   // seconds
            $table->unsignedBigInteger('filesize')->nullable(); // bytes
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_media');
    }
};
