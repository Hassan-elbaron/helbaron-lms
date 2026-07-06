<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // THE analytics read model: pre-aggregated metric values per period + dimension.
        Schema::create('metric_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('metric_key');
            $table->string('granularity', 16);
            $table->date('period');
            $table->string('dimension_key')->default('');
            $table->string('dimension_value')->default('');
            $table->bigInteger('value')->default(0);
            $table->timestamps();

            $table->unique(['metric_key', 'granularity', 'period', 'dimension_key', 'dimension_value'], 'metric_snapshots_unique');
            $table->index(['metric_key', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metric_snapshots');
    }
};
