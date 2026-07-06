<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_team_members', function (Blueprint $table) {
            $table->foreignId('team_id')->constrained('crm_teams')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('organization_members')->cascadeOnDelete();
            $table->string('role', 24)->default('member');
            $table->primary(['team_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_team_members');
    }
};
