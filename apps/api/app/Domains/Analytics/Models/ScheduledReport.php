<?php

namespace App\Domains\Analytics\Models;

use App\Domains\Analytics\Enums\ScheduleFrequency;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledReport extends Model
{
    use HasPublicId;

    protected $fillable = ['report_definition_id', 'frequency', 'next_run_at', 'is_active'];

    protected function casts(): array
    {
        return ['frequency' => ScheduleFrequency::class, 'next_run_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ReportDefinition::class, 'report_definition_id');
    }
}
