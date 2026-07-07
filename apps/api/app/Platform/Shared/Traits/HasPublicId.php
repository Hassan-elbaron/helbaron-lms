<?php

namespace App\Platform\Shared\Traits;

use App\Platform\Shared\Helpers\Uuid;

/**
 * Gives a model a UUIDv7 `public_id` external identifier and route-binds on it, so internal
 * bigint primary keys are never exposed. Auto-generates the id on create when absent.
 *
 * Expects a `public_id` column (see the Blueprint::publicId() macro).
 */
trait HasPublicId
{
    public static function bootHasPublicId(): void
    {
        static::creating(function ($model): void {
            if (empty($model->public_id)) {
                $model->public_id = Uuid::v7();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
