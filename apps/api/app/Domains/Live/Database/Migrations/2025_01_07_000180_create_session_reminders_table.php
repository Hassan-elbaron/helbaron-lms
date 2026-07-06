<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_reminders', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('session_id')->constrained('live_sessions')->cascadeOnDelete();
            $table->unsignedInteger('offset_minutes');
            $table->string('channel', 24)->default('email');
            $table->timestamp('scheduled_at');
            $table->string('status', 16)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_reminders');
    }
};
