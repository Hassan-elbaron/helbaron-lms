<?php

namespace App\Platform\Features\Http\Middleware;

use App\Platform\Features\Services\FeatureFlagService;
use App\Platform\Identity\Models\User;
use App\Platform\Shared\Audit\AuditLogger;
use App\Platform\Shared\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Route guard that enforces a feature flag: `->middleware('feature:events')`.
 *
 * Behaviour (all additive — with default-on flags and the admin override, a normal run is
 * unaffected):
 *   - Platform admins (super_admin / admin) ALWAYS pass, so an admin can never be locked out of
 *     a surface they need to re-enable.
 *   - Otherwise the flag is resolved via FeatureFlagService (which also honours the FEATURE_{KEY}
 *     env kill-switch). Missing / default-on ⇒ pass.
 *   - When disabled the request is refused with a 404 (never 403) so a switched-off feature looks
 *     non-existent rather than merely forbidden. JSON/API callers get the standard error envelope;
 *     web callers get abort(404). A single `feature.blocked` audit row is written per block.
 *   - Fail-open: if flag resolution throws, the request is allowed through — a broken flag
 *     subsystem must never take down a working feature.
 */
class EnsureFeatureEnabled
{
    /** Roles that bypass every feature gate (never lock an administrator out). */
    private const ADMIN_ROLES = ['super_admin', 'admin'];

    public function __construct(
        private readonly FeatureFlagService $features,
        private readonly AuditLogger $audit,
    ) {}

    public function handle(Request $request, Closure $next, string $key): Response
    {
        $user = $this->resolveUser($request);

        // Admin override — administrators always pass, regardless of the flag.
        if ($user !== null && $user->hasAnyRole(self::ADMIN_ROLES)) {
            return $next($request);
        }

        try {
            $enabled = $this->features->isEnabled($key, $user);
        } catch (Throwable $e) {
            // Fail-open: never break a working feature because the flag subsystem errored.
            report($e);

            return $next($request);
        }

        if ($enabled) {
            return $next($request);
        }

        return $this->blocked($request, $key);
    }

    /** Resolve the acting user from the default guard, falling back to the Sanctum token. */
    private function resolveUser(Request $request): ?User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            $user = $request->user('sanctum');
        }

        return $user instanceof User ? $user : null;
    }

    /** Refuse a disabled-feature request as a 404 and record a single audit row. */
    private function blocked(Request $request, string $key): Response
    {
        try {
            $this->audit->log('feature.blocked', null, [
                'key' => $key,
                'path' => $request->path(),
            ]);
        } catch (Throwable $e) {
            // Auditing must never affect the response the caller receives.
            report($e);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return ApiResponse::error(
                'NOT_FOUND',
                'The requested resource was not found.',
                status: 404,
            );
        }

        abort(404);
    }
}
