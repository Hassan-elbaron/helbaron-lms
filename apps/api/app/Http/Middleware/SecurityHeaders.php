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

        // The Filament admin panel (Blade/Livewire) is served by this same app and needs a relaxed
        // CSP so its styles/scripts/fonts load and its login form can submit; the JSON API keeps the
        // locked-down default. Asset responses (/css/filament, /js/filament, ...) are unaffected by
        // their own CSP, so only the HTML/Livewire paths need the web policy.
        $webPaths = (array) config('security.web_paths', []);
        $cspKey = ($webPaths !== [] && $request->is(...$webPaths)) ? 'security.csp_web' : 'security.csp';
        $csp = (string) config($cspKey, '');
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
