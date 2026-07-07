<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy;

/**
 * Queued-job middleware that runs a system job with tenancy bypassed.
 *
 * Attach via a job's `middleware()` method (`return [new WithoutTenancy()];`) for background jobs
 * that must operate across tenants (system maintenance, platform-wide recomputation). Ordinary
 * tenant-scoped jobs should NOT use this — they set the tenant explicitly instead.
 */
final class WithoutTenancy
{
    public function handle(object $job, callable $next): mixed
    {
        return app(TenantContext::class)->runWithoutTenancy(static fn () => $next($job));
    }
}
