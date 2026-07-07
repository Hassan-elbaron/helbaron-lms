<?php

namespace App\Contexts\Commerce\Models;

use App\Contexts\Commerce\Enums\InvoiceStatus;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasPublicId;

    protected $fillable = ['order_id', 'number', 'status', 'currency', 'total_minor', 'issued_at', 'paid_at'];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'total_minor' => 'integer',
            'issued_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
