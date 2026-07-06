<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The delivery ledger + dead-letter metadata. Idempotent per (notification, channel).
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('notification_id')->constrained('notifications')->cascadeOnDelete();
            $table->string('channel', 16);
            $table->string('provider', 24)->nullable();
            $table->string('status', 16)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->string('dedup_key')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('dead_at')->nullable();
            $table->timestamps();

            $table->unique(['notification_id', 'channel']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
