<?php

namespace App\Domains\Crm\Models;

use App\Domains\Crm\Concerns\HasActivities;
use App\Domains\Crm\Concerns\HasNotes;
use App\Domains\Crm\Concerns\HasTags;
use App\Domains\Crm\Concerns\HasTasks;
use App\Domains\Crm\Database\Factories\LeadFactory;
use App\Domains\Crm\Enums\LeadStatus;
use App\Platform\Identity\Models\User;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasActivities;

    use HasFactory;
    use HasNotes;
    use HasPublicId;
    use HasTags;
    use HasTasks;
    use SoftDeletes;

    protected $table = 'crm_leads';

    protected $fillable = [
        'pipeline_id', 'stage_id', 'company_id', 'contact_id', 'owner_id',
        'name', 'email', 'phone', 'source', 'status', 'value_minor', 'currency',
    ];

    protected function casts(): array
    {
        return ['status' => LeadStatus::class, 'value_minor' => 'integer'];
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function isConverted(): bool
    {
        return $this->status === LeadStatus::Converted;
    }

    protected static function newFactory(): LeadFactory
    {
        return LeadFactory::new();
    }
}
