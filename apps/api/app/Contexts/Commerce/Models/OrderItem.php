<?php

namespace App\Contexts\Commerce\Models;

use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasPublicId;

    protected $fillable = ['order_id', 'product_id', 'title', 'unit_amount_minor'];

    protected function casts(): array
    {
        return ['unit_amount_minor' => 'integer'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
