<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->string('key');
            $table->unsignedInteger('version')->default(1);
            $table->string('name');
            $table->longText('html');           // Blade-free HTML with {{ placeholders }}
            $table->string('orientation', 16)->default('landscape');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['key', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_templates');
    }
};
