<?php

namespace App\Domains\Catalog\Services;

use App\Platform\Shared\Helpers\Slug;
use App\Platform\Shared\Services\BaseService;
use Illuminate\Database\Eloquent\Model;

/**
 * Generates slugs that are unique within a table/column, ignoring an optional record id
 * (for updates). Thin wrapper over the shared Slug helper.
 */
class SlugService extends BaseService
{
    public function forModel(string $modelClass, string $source, ?int $ignoreId = null, string $column = 'slug'): string
    {
        /** @var Model $probe */
        $probe = new $modelClass;
        $table = $probe->getTable();

        return Slug::unique($source, function (string $candidate) use ($modelClass, $column, $ignoreId): bool {
            $query = $modelClass::query()->where($column, $candidate);
            if ($ignoreId !== null) {
                $query->whereKeyNot($ignoreId);
            }

            return $query->exists();
        });
    }
}
