<?php

namespace App\Contexts\Commerce\Models;

use App\Contexts\Commerce\Enums\TransactionStatus;
use App\Contexts\Commerce\Enums\TransactionType;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasPublicId;

    protected $fillable = [
        'order_id', 'provider', 'provider_reference', 'type', 'status', 'amount_minor', 'currency',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'status' => TransactionStatus::class,
            'amount_minor' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
