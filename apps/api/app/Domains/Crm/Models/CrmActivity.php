<?php

namespace App\Domains\Crm\Models;

use App\Domains\Crm\Enums\ActivityType;
use App\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CrmActivity extends Model
{
    use HasPublicId;

    protected $fillable = ['subject_type', 'subject_id', 'type', 'description', 'user_id', 'occurred_at'];

    protected function casts(): array
    {
        return ['type' => ActivityType::class, 'occurred_at' => 'datetime'];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
