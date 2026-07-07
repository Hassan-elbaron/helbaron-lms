<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('provider', 24);
            $table->string('provider_reference')->nullable();
            $table->string('type', 16);      // charge | refund
            $table->string('status', 16);    // pending | succeeded | failed
            $table->unsignedInteger('amount_minor');
            $table->string('currency', 3);
            $table->timestamps();

            $table->index(['order_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
