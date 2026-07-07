<?php

namespace App\Platform\Shared\Traits;

use App\Platform\Shared\Helpers\Slug;

/**
 * Auto-generates a URL slug from a source attribute (default `name`) into a `slug` column
 * when the slug is empty. Override slugSource()/slugColumn() to customize. No business logic.
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function ($model): void {
            $column = $model->slugColumn();

            if (empty($model->{$column})) {
                $source = (string) ($model->{$model->slugSource()} ?? '');

                if ($source !== '') {
                    $model->{$column} = Slug::make($source);
                }
            }
        });
    }

    public function slugSource(): string
    {
        return 'name';
    }

    public function slugColumn(): string
    {
        return 'slug';
    }
}
