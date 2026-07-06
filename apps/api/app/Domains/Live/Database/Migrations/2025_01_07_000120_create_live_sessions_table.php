<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_sessions', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('live_course_id')->nullable()->constrained('live_courses')->nullOnDelete();
            $table->foreignId('series_id')->nullable()->constrained('session_series')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 16)->default('scheduled');
            $table->string('timezone', 64)->default('UTC');
            $table->timestamp('starts_at');   // stored in UTC
            $table->timestamp('ends_at');     // stored in UTC
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('waiting_room')->default(true);
            $table->string('meeting_provider', 24)->nullable();
            $table->string('meeting_external_id')->nullable();
            $table->string('join_url')->nullable();   // raw provider URL — never exposed directly
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_sessions');
    }
};
