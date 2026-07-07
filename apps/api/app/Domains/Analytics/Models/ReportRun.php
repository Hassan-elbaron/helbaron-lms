<?php

namespace App\Domains\Analytics\Models;

use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportRun extends Model
{
    use HasPublicId;

    protected $fillable = ['report_definition_id', 'status', 'params', 'result', 'ran_at'];

    protected function casts(): array
    {
        return ['params' => 'array', 'result' => 'array', 'ran_at' => 'datetime'];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ReportDefinition::class, 'report_definition_id');
    }
}
