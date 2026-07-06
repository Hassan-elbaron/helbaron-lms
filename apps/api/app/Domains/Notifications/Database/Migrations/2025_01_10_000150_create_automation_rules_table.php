<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_rules', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->string('name');
            $table->string('trigger_type', 24)->default('event');
            $table->string('trigger_key')->nullable();  // event class or schedule key
            $table->json('conditions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('automation_actions', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('automation_rule_id')->constrained('automation_rules')->cascadeOnDelete();
            $table->string('action_type', 24)->default('send_notification');
            $table->string('template_key');
            $table->string('category', 24)->default('system');
            $table->json('channels')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('scheduled_automations', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('automation_rule_id')->constrained('automation_rules')->cascadeOnDelete();
            $table->timestamp('run_at');
            $table->string('status', 16)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_automations');
        Schema::dropIfExists('automation_actions');
        Schema::dropIfExists('automation_rules');
    }
};
