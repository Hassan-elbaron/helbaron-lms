<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_series', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('live_course_id')->constrained('live_courses')->cascadeOnDelete();
            $table->string('title');
            $table->string('recurrence')->nullable(); // e.g. RRULE string (not expanded here)
            $table->string('timezone', 64)->default('UTC');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_series');
    }
};
