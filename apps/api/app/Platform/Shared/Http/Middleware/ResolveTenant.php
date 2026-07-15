<?php

declare(strict_types=1);

namespace App\Platform\Shared\Http\Middleware;

use App\Platform\Shared\Tenancy\TenantContext;
use App\Platform\Shared\Tenancy\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Populates the TenantContext for the current request from the TenantResolver.
 *
 * FOUNDATION ONLY (Sprint 1 / A2-S01): registered as a named middleware alias ("tenant.resolve")
 * but NOT applied to any route group, so it does not run in the request pipeline yet and changes
 * no behavior. It is deliberately non-throwing and never denies a request — enforcement
 * (deny-on-missing, global scope) is added in later A2 stories. Applying this alias is the first
 * step of A2-S02.
 */
final class ResolveTenant
{
    public function __construct(
        private readonly TenantResolver $resolver,
        private readonly TenantContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $tenant = $this->resolver->resolve();

            if ($tenant !== null) {
                $this->context->set($tenant);
            }
        } catch (\Throwable) {
            // Never break the request during the foundation phase; resolution failures leave the
            // context empty. Enforcement of a required tenant is a later, explicit story.
        }

        return $next($request);
    }
}
