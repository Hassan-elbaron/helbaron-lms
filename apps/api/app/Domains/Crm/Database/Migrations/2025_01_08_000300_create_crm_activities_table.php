<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Polymorphic timeline: attach to leads, companies, contacts, organizations, requests.
        Schema::create('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('type', 24);
            $table->string('description')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_activities');
    }
};
