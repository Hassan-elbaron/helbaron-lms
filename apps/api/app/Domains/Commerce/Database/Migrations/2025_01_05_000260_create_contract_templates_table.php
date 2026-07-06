<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Versioned templates: (key, version) is unique; is_active marks the current version.
        Schema::create('contract_templates', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->string('key');
            $table->unsignedInteger('version')->default(1);
            $table->string('title');
            $table->longText('body');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['key', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_templates');
    }
};
