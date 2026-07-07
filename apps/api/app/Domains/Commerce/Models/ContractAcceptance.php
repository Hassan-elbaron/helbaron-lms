<?php

namespace App\Domains\Commerce\Models;

use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable acceptance audit row.
 */
class ContractAcceptance extends Model
{
    use HasPublicId;

    protected $fillable = [
        'contract_id', 'user_id', 'template_version', 'body_hash', 'ip', 'user_agent', 'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'template_version' => 'integer',
            'accepted_at' => 'datetime',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
