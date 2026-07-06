<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds security response headers. Values come from config/security.php so they can be tuned per
 * environment. HSTS is only emitted over HTTPS (production) to avoid poisoning local http.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $headers = (array) config('security.headers', []);
        foreach ($headers as $name => $value) {
            if ($value !== null && $value !== '' && ! $response->headers->has($name)) {
                $response->headers->set($name, (string) $value);
            }
        }

        $csp = (string) config('security.csp', '');
        if ($csp !== '') {
            $response->headers->set('Content-Security-Policy', $csp);
        }

        if ($request->isSecure() && (bool) config('security.hsts.enabled', true)) {
            $maxAge = (int) config('security.hsts.max_age', 31536000);
            $sub = config('security.hsts.include_subdomains', true) ? '; includeSubDomains' : '';
            $preload = config('security.hsts.preload', true) ? '; preload' : '';
            $response->headers->set('Strict-Transport-Security', "max-age={$maxAge}{$sub}{$preload}");
        }

        return $response;
    }
}
