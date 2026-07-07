<?php

namespace App\Platform\Shared\Services;

use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Base class for domain Services (reusable domain logic). Provides container resolution
 * and a transaction helper. No business logic lives here.
 */
abstract class BaseService
{
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    protected function transaction(Closure $callback): mixed
    {
        return DB::transaction($callback);
    }
}
