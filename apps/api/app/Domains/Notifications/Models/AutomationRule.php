<?php

namespace App\Domains\Notifications\Models;

use App\Domains\Notifications\Enums\AutomationTriggerType;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationRule extends Model
{
    use HasPublicId;

    protected $fillable = ['name', 'trigger_type', 'trigger_key', 'conditions', 'is_active'];

    protected function casts(): array
    {
        return ['trigger_type' => AutomationTriggerType::class, 'conditions' => 'array', 'is_active' => 'boolean'];
    }

    public function actions(): HasMany
    {
        return $this->hasMany(AutomationAction::class);
    }
}
