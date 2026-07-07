<?php

namespace App\Domains\Crm\Models;

use App\Domains\Crm\Enums\ConsultingProjectStatus;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsultingProject extends Model
{
    use HasPublicId;
    use SoftDeletes;

    protected $fillable = ['consulting_request_id', 'organization_id', 'name', 'status', 'started_at', 'ended_at'];

    protected function casts(): array
    {
        return ['status' => ConsultingProjectStatus::class, 'started_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ConsultingSession::class);
    }
}
