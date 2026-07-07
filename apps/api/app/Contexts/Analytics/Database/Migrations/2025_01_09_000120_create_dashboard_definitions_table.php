<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_definitions', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('dashboard_id')->constrained('dashboard_definitions')->cascadeOnDelete();
            $table->string('title');
            $table->string('metric_key')->nullable();
            $table->string('type', 24)->default('kpi'); // kpi | table | funnel | cohort
            $table->json('config')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
        Schema::dropIfExists('dashboard_definitions');
    }
};
