<?php

namespace App\Domains\Crm\Models;

use App\Domains\Crm\Enums\MemberRole;
use App\Domains\Crm\Enums\MemberStatus;
use App\Platform\Identity\Models\User;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationMember extends Model
{
    use HasPublicId;

    protected $fillable = ['organization_id', 'user_id', 'email', 'role', 'status', 'invited_at', 'joined_at'];

    protected function casts(): array
    {
        return [
            'role' => MemberRole::class,
            'status' => MemberStatus::class,
            'invited_at' => 'datetime',
            'joined_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
