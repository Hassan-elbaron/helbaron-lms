<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Single-row settings for issuer + default signature.
        Schema::create('certificate_settings', function (Blueprint $table) {
            $table->id();
            $table->string('issuer_name');
            $table->string('signature_name')->nullable();
            $table->string('signature_title')->nullable();
            $table->string('signature_image_path')->nullable(); // never exposed via API
            $table->foreignId('default_template_id')->nullable()->constrained('certificate_templates')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_settings');
    }
};
