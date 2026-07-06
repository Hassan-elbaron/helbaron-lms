<?php

namespace App\Domains\Identity\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires confirmed multi-factor authentication for admin-panel access when ADMIN_REQUIRE_MFA
 * is enabled. Runs after Filament's Authenticate middleware, so a user is always present. Kept
 * config-gated (default off) so local/dev environments are not locked out before MFA is set up.
 */
class EnforceAdminMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('admin.require_mfa', false)) {
            return $next($request);
        }

        $user = $request->user();

        if ($user !== null && ! $this->hasConfirmedMfa($user)) {
            abort(403, 'Multi-factor authentication is required to access the admin panel.');
        }

        return $next($request);
    }

    private function hasConfirmedMfa(object $user): bool
    {
        return (bool) ($user->mfa_enabled ?? false) && $user->two_factor_confirmed_at !== null;
    }
}
