<?php

namespace App\Domains\Crm\Models;

use App\Domains\Crm\Enums\ActivityType;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    /**
     * Acting user. Resolved via auth config (not a concrete Identity import) so CRM keeps
     * no compile-time dependency on the Identity context.
     *
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        /** @var class-string<Model> $userModel */
        $userModel = config('auth.providers.users.model');

        return $this->belongsTo($userModel, 'user_id');
    }
}
