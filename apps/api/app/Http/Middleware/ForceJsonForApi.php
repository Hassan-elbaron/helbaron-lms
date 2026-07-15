<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The API surface (/api/*) is JSON-only. This middleware normalizes an api request to always
 * "expect JSON" by forcing its Accept header to application/json when the client sent something
 * else (e.g. the default wildcard Accept header). That guarantees framework error handling takes the JSON
 * path for api/* — most importantly, an unauthenticated request renders the standard JSON 401
 * envelope instead of trying to redirect to a (non-existent) named `login` route, which would
 * otherwise throw RouteNotFoundException and surface as HTTP 500.
 *
 * Scoped to the 'api' group only, so web/marketing and Filament panels (which DO have a login
 * route and their own content negotiation) are never affected. Responses set their own
 * Content-Type, so file/stream endpoints are unaffected.
 */
class ForceJsonForApi
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/*') && ! $request->expectsJson()) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}
