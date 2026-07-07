<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Records which courses an order granted — makes fulfillment idempotent.
        Schema::create('order_course_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->timestamp('granted_at')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_course_grants');
    }
};
