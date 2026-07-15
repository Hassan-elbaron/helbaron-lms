<?php

namespace App\Platform\Shared\Audit;

use Illuminate\Database\Eloquent\Model;

/**
 * Append-only audit-trail entry for privileged actions (refunds, revocations, ...).
 * Immutable by design: rows are only ever inserted — updates and deletes are blocked
 * at the model level. Written via AuditLogger, never directly.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    /** @var array<int, string> */
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Append-only: silently refuse updates/deletes instead of corrupting the trail.
        static::updating(fn (): bool => false);
        static::deleting(fn (): bool => false);
    }
}
