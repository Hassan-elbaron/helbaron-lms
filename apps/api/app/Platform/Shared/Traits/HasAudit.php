<?php

namespace App\Platform\Shared\Traits;

/**
 * Stamps `created_by` / `updated_by` with the current authenticated user id (if any) on
 * write. It only *reads* the current user id via the framework helper — it does not
 * implement authentication. Safe when unauthenticated (columns stay null).
 *
 * Expects `created_by` / `updated_by` columns (see the Blueprint::auditColumns() macro).
 */
trait HasAudit
{
    public static function bootHasAudit(): void
    {
        static::creating(function ($model): void {
            $id = self::currentActorId();
            if ($id !== null) {
                $model->created_by ??= $id;
                $model->updated_by ??= $id;
            }
        });

        static::updating(function ($model): void {
            $id = self::currentActorId();
            if ($id !== null) {
                $model->updated_by = $id;
            }
        });
    }

    private static function currentActorId(): int|string|null
    {
        // Reads the current user id if a guard is resolved; null otherwise. No auth logic.
        return function_exists('auth') ? auth()->id() : null;
    }
}
