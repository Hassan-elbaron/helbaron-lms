<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Metadata ONLY — HElbaron does not process or host recording media.
        Schema::create('session_recordings', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('session_id')->constrained('live_sessions')->cascadeOnDelete();
            $table->string('provider', 24);
            $table->string('external_id')->nullable();
            $table->string('url')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('status', 16)->default('none');
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_recordings');
    }
};
