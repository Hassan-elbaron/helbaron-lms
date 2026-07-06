<?php

namespace App\Domains\Crm\Models;

use App\Domains\Crm\Concerns\HasActivities;
use App\Domains\Crm\Concerns\HasNotes;
use App\Domains\Crm\Database\Factories\ConsultingRequestFactory;
use App\Domains\Crm\Enums\ConsultingRequestStatus;
use App\Domains\Identity\Models\User;
use App\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsultingRequest extends Model
{
    /** @use HasFactory<ConsultingRequestFactory> */
    use HasActivities;

    use HasFactory;
    use HasNotes;
    use HasPublicId;
    use SoftDeletes;

    protected $fillable = [
        'organization_id', 'contact_id', 'requested_by', 'subject', 'description', 'status', 'sla_due_at',
    ];

    protected function casts(): array
    {
        return ['status' => ConsultingRequestStatus::class, 'sla_due_at' => 'datetime'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    protected static function newFactory(): ConsultingRequestFactory
    {
        return ConsultingRequestFactory::new();
    }
}
