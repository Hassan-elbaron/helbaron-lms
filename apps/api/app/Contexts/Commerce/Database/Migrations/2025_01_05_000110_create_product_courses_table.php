<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A product grants one or more courses on purchase (single course or bundle).
        Schema::create('product_courses', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->primary(['product_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_courses');
    }
};
