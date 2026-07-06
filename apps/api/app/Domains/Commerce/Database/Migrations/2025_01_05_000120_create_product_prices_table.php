<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Multi-currency pricing with optional time-boxed sale price. Amounts are minor units.
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('currency', 3);
            $table->unsignedInteger('amount_minor');
            $table->unsignedInteger('sale_amount_minor')->nullable();
            $table->timestamp('sale_starts_at')->nullable();
            $table->timestamp('sale_ends_at')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['product_id', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
