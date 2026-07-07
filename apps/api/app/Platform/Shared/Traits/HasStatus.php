<?php

namespace App\Platform\Shared\Traits;

use App\Platform\Shared\Enums\Status;

/**
 * Helpers for a generic `status` column backed by the shared Status enum.
 * Cast the column in the model: protected $casts = ['status' => Status::class];
 */
trait HasStatus
{
    public function isStatus(Status $status): bool
    {
        return $this->status === $status || $this->status === $status->value;
    }

    public function isActive(): bool
    {
        return $this->isStatus(Status::Active);
    }

    /** Query scope: whereStatus(Status::Active). */
    public function scopeWhereStatus($query, Status|string $status)
    {
        return $query->where('status', $status instanceof Status ? $status->value : $status);
    }
}
