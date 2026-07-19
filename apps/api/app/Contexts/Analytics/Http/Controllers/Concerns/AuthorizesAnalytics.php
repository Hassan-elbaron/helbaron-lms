<?php

namespace App\Contexts\Analytics\Http\Controllers\Concerns;

use App\Platform\Identity\Contracts\Actor;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Authorization for the metric-driven analytics endpoints (KPIs, dashboards, report definitions).
 *
 * These three controllers previously carried no authorization beyond `auth:sanctum`, so any
 * authenticated user — including a learner — could read platform-wide figures. The policies to
 * prevent that already existed but were never invoked; this trait is where they finally are.
 *
 * WHY ROLES AND NOT `$user->can('analytics.view')`:
 * Spatie resolves a guard when checking a permission, and under `auth:sanctum` that guard is not
 * the `web` one the permissions are seeded against — so `can()` returns false even for an admin
 * who genuinely holds the permission. `hasRole()` has no such problem because it matches by name.
 * This is why every other API controller in the codebase (ReportInsightController included) gates
 * on roles, and this trait follows that convention rather than fighting it. The permissions remain
 * seeded and are still the operative check inside Filament, which runs on the `web` guard.
 *
 * Money is separated from reach deliberately: instructors may read engagement figures, but revenue
 * stays with administrators.
 */
trait AuthorizesAnalytics
{
    /** @var list<string> May read the shared metric surface at all. */
    private const ANALYTICS_ROLES = ['super_admin', 'admin', 'instructor'];

    /** @var list<string> May additionally see currency-denominated metrics. */
    private const REVENUE_ROLES = ['super_admin', 'admin'];

    /** Any analytics read. Throws 403 rather than 404: the endpoints themselves are not secret. */
    protected function assertCanViewAnalytics(Request $request): void
    {
        if (! $this->actorHasAnyRole($request, self::ANALYTICS_ROLES)) {
            throw new AccessDeniedHttpException('Analytics access required.');
        }
    }

    /**
     * May this caller see currency-denominated metrics?
     *
     * Callers filter on this rather than rejecting the whole request: a dashboard that happens to
     * include a revenue widget should still render its other cards for someone without the
     * permission, not fail outright.
     */
    protected function canViewRevenue(Request $request): bool
    {
        return $this->actorHasAnyRole($request, self::REVENUE_ROLES);
    }

    /** @param  list<string>  $roles */
    private function actorHasAnyRole(Request $request, array $roles): bool
    {
        $user = $request->user();

        return $user instanceof Actor && $user->hasRole($roles);
    }
}
