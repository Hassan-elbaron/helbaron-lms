<?php

namespace App\Domains\Commerce\Models;

use App\Domains\Commerce\Database\Factories\OrderFactory;
use App\Domains\Commerce\Enums\OrderStatus;
use App\Domains\Identity\Models\User;
use App\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    use HasPublicId;
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'status', 'currency', 'subtotal_minor', 'discount_minor', 'total_minor',
        'coupon_id', 'placed_at', 'paid_at', 'fulfilled_at', 'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal_minor' => 'integer',
            'discount_minor' => 'integer',
            'total_minor' => 'integer',
            'placed_at' => 'datetime',
            'paid_at' => 'datetime',
            'fulfilled_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class);
    }

    public function grants(): HasMany
    {
        return $this->hasMany(OrderCourseGrant::class);
    }

    public function isPaid(): bool
    {
        return $this->status === OrderStatus::Paid || $this->paid_at !== null;
    }

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }
}
