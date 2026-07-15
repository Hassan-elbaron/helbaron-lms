<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD/IA (ST-01 Account/Profile): learner profile must capture Country and City. Additive,
 * nullable columns — backward compatible; no existing data affected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->string('country', 2)->nullable()->after('date_of_birth'); // ISO 3166-1 alpha-2
            $table->string('city')->nullable()->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->dropColumn(['country', 'city']);
        });
    }
};
