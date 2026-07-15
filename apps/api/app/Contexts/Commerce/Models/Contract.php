<?php

namespace App\Contexts\Commerce\Models;

use App\Contexts\Commerce\Enums\ContractStatus;
use App\Platform\Shared\Traits\HasPublicId;
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
