<?php

namespace App\Domains\Notifications\Models;

use App\Domains\Identity\Models\User;
use App\Domains\Notifications\Database\Factories\NotificationFactory;
use App\Domains\Notifications\Enums\NotificationCategory;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    /** @use HasFactory<NotificationFactory> */
    use HasFactory;

    use HasPublicId;

    protected $fillable = ['user_id', 'category', 'type', 'title', 'body', 'data', 'locale', 'read_at', 'archived_at'];

    protected function casts(): array
    {
        return [
            'category' => NotificationCategory::class,
            'data' => 'array',
            'read_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    protected static function newFactory(): NotificationFactory
    {
        return NotificationFactory::new();
    }
}
