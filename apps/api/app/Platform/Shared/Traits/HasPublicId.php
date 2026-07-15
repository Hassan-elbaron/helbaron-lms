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

    /**
     * Resolve an implicit route binding on the public_id. public_id is a uuid-typed column, so a
     * non-UUID path segment (e.g. /organizations/34) must NOT reach the database — on Postgres that
     * raises SQLSTATE[22P02] "invalid input syntax for type uuid" and surfaces as a 500. Guard the
     * format first and return null for a malformed value so the framework renders a clean 404
     * (ModelNotFound) instead. Valid UUIDs fall through to the default query unchanged.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if (($field ?? $this->getRouteKeyName()) === 'public_id' && ! Uuid::isValid((string) $value)) {
            return null;
        }

        return parent::resolveRouteBinding($value, $field);
    }
}
