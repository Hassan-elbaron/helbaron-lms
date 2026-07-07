<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_definitions', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->string('name');
            $table->string('type', 16)->default('metric');
            $table->json('metric_keys')->nullable();
            $table->json('filters')->nullable();
            $table->string('visibility', 16)->default('private');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('report_runs', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('report_definition_id')->constrained('report_definitions')->cascadeOnDelete();
            $table->string('status', 16)->default('completed');
            $table->json('params')->nullable();
            $table->json('result')->nullable();
            $table->timestamp('ran_at')->nullable();
            $table->timestamps();
        });

        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('report_definition_id')->constrained('report_definitions')->cascadeOnDelete();
            $table->string('frequency', 16);
            $table->timestamp('next_run_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_reports');
        Schema::dropIfExists('report_runs');
        Schema::dropIfExists('report_definitions');
    }
};
