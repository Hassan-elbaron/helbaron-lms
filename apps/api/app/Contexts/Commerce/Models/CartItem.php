<?php

namespace App\Contexts\Commerce\Models;

use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasPublicId;

    protected $fillable = ['cart_id', 'product_id', 'unit_amount_minor'];

    protected function casts(): array
    {
        return ['unit_amount_minor' => 'integer'];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
