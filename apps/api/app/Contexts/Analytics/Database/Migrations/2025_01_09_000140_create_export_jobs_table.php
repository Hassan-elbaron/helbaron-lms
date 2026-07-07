<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_jobs', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('format', 8);              // csv | xlsx
            $table->string('status', 16)->default('queued');
            $table->string('source', 24);             // report | metric
            $table->json('params')->nullable();
            $table->string('file_path')->nullable();  // storage path — never exposed
            $table->unsignedInteger('row_count')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_jobs');
    }
};
