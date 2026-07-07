<?php

namespace App\Contexts\Analytics\Models;

use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DashboardDefinition extends Model
{
    use HasPublicId;

    protected $fillable = ['key', 'name', 'description', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function widgets(): HasMany
    {
        return $this->hasMany(DashboardWidget::class, 'dashboard_id')->orderBy('position');
    }
}
