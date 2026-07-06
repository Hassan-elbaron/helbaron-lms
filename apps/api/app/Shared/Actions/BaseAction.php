<?php

namespace App\Shared\Actions;

use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Base class for single-purpose use-case Actions. Actions orchestrate one operation and
 * own the transaction boundary. This base provides container resolution and a transaction
 * helper only — no business logic.
 *
 * Convention: concrete actions expose an `execute(...)`/`handle(...)` method of their own.
 */
abstract class BaseAction
{
    /** Resolve the action from the container. */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Run a callback inside a database transaction.
     *
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
