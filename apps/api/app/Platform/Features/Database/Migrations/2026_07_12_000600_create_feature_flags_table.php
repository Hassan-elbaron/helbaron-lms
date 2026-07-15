<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Feature flags — additive, presentation/rollout gating only. A flag is evaluated by
        // App\Platform\Features\Services\FeatureFlagService. IMPORTANT default-on semantics:
        // a MISSING flag row resolves to TRUE, and `is_enabled` defaults to true, so a flag never
        // hides a working feature by accident. `environment` (null = all), `roles` (null/[] = all),
        // `rollout_percentage` (null = 100 / everyone), and the starts_at/ends_at window further
        // constrain an ENABLED flag.
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->string('key', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->string('environment')->nullable();
            $table->json('roles')->nullable();
            $table->unsignedTinyInteger('rollout_percentage')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('owner')->nullable();
            $table->timestamps();

            $table->index('is_enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
    }
};
