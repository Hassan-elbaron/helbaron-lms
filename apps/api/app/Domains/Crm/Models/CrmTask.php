<?php

namespace App\Domains\Crm\Models;

use App\Domains\Crm\Enums\TaskStatus;
use App\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CrmTask extends Model
{
    use HasPublicId;

    protected $fillable = ['taskable_type', 'taskable_id', 'title', 'status', 'due_at', 'assigned_to'];

    protected function casts(): array
    {
        return ['status' => TaskStatus::class, 'due_at' => 'datetime'];
    }

    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }
}
