<?php

namespace App\Domains\Crm\Models;

use App\Domains\Crm\Enums\ConsultingSessionStatus;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultingSession extends Model
{
    use HasPublicId;

    protected $fillable = ['consulting_project_id', 'title', 'scheduled_at', 'duration_minutes', 'status'];

    protected function casts(): array
    {
        return ['status' => ConsultingSessionStatus::class, 'scheduled_at' => 'datetime', 'duration_minutes' => 'integer'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ConsultingProject::class, 'consulting_project_id');
    }
}
