<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('certificate_templates')->nullOnDelete();

            $table->string('number')->unique();
            $table->string('verification_code', 32)->unique();
            $table->string('status', 16)->default('issued');

            // Digital-signature metadata (not PKI): a signed hash + captured signer identity.
            $table->string('signature_name')->nullable();
            $table->string('signature_title')->nullable();
            $table->string('signature_hash', 64)->nullable();

            $table->string('pdf_path')->nullable();          // storage path — never exposed
            $table->timestamp('pdf_generated_at')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('issued_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('reissued_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'course_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
