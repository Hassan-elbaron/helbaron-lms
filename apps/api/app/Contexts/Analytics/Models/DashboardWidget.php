<?php

namespace App\Contexts\Analytics\Models;

use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardWidget extends Model
{
    use HasPublicId;

    protected $fillable = ['dashboard_id', 'title', 'metric_key', 'type', 'config', 'position'];

    protected function casts(): array
    {
        return ['config' => 'array', 'position' => 'integer'];
    }

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(DashboardDefinition::class, 'dashboard_id');
    }
}
