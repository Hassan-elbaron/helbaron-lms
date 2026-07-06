<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Separate from Identity's tenant `organizations` — this is the CRM/corporate account.
        Schema::create('crm_organizations', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status', 16)->default('prospect');
            $table->string('size', 16)->nullable();
            $table->string('website')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_organizations');
    }
};
