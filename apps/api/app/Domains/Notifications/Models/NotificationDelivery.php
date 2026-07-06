<?php

namespace App\Domains\Notifications\Models;

use App\Domains\Notifications\Enums\Channel;
use App\Domains\Notifications\Enums\DeliveryStatus;
use App\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDelivery extends Model
{
    use HasPublicId;

    protected $fillable = [
        'notification_id', 'channel', 'provider', 'status', 'attempts', 'last_error', 'dedup_key', 'sent_at', 'dead_at',
    ];

    protected function casts(): array
    {
        return [
            'channel' => Channel::class,
            'status' => DeliveryStatus::class,
            'attempts' => 'integer',
            'sent_at' => 'datetime',
            'dead_at' => 'datetime',
        ];
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    public function isPending(): bool
    {
        return $this->status === DeliveryStatus::Pending;
    }
}
