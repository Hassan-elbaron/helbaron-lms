<?php

namespace App\Domains\Notifications\Models;

use App\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledAutomation extends Model
{
    use HasPublicId;

    protected $fillable = ['automation_rule_id', 'run_at', 'status'];

    protected function casts(): array
    {
        return ['run_at' => 'datetime'];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }
}
