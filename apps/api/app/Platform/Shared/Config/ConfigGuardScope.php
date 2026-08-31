<?php

namespace App\Platform\Shared\Config;

use Illuminate\Contracts\Foundation\Application;

/**
 * Which processes must refuse to start on an unsafe production configuration.
 *
 * This is the decision half of AppServiceProvider::guardProductionConfig(), separated so it can be
 * tested directly. The guard itself runs inside an `app->booted()` closure during bootstrap, which
 * is not re-entrant in a test; the interesting logic is the scope, so the scope is what is tested.
 *
 * THE RULE. A production process is guarded when it does production WORK. That is the web path and
 * the long-running workers. It is not the operator tooling — config:validate, env:validate, migrate,
 * tinker, install:academy — because that tooling is how a bad configuration gets diagnosed and
 * fixed, and a guard that blocks the diagnosis makes the outage longer.
 *
 * Before this, `runningInConsole()` exempted the whole console, so a queue worker booted happily
 * against a configuration the web container had already refused. That is the worse half: the worker
 * is the process that charges cards, sends mail and issues certificates, and it does it with nobody
 * watching.
 */
final class ConfigGuardScope
{
    /**
     * Long-running console processes that are production workloads rather than operator tooling.
     *
     * @var list<string>
     */
    public const WORKER_COMMANDS = [
        'horizon',
        'horizon:work',
        'horizon:supervisor',
        'queue:work',
        'queue:listen',
        'schedule:work',
        'schedule:run',
        'octane:start',
    ];

    /**
     * True when this process must be blocked by an unsafe production configuration.
     */
    public static function appliesTo(Application $app): bool
    {
        if (! $app->environment('production')) {
            return false;
        }

        if (! $app->runningInConsole()) {
            return true;
        }

        return $app->runningConsoleCommand(self::WORKER_COMMANDS);
    }
}
