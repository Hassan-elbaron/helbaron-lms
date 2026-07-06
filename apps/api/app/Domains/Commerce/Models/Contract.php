<?php

namespace App\Domains\Commerce\Models;

use App\Domains\Commerce\Enums\ContractStatus;
use App\Domains\Identity\Models\User;
use App\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasPublicId;

    protected $fillable = ['user_id', 'order_id', 'template_id', 'status', 'accepted_at'];

    protected function casts(): array
    {
        return [
            'status' => ContractStatus::class,
            'accepted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class, 'template_id');
    }

    public function acceptances(): HasMany
    {
        return $this->hasMany(ContractAcceptance::class);
    }

    public function isAccepted(): bool
    {
        return $this->status === ContractStatus::Accepted;
    }
}
