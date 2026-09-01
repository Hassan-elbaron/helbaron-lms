<?php

namespace App\Platform\Shared\Http;

use Illuminate\Http\Middleware\TrustProxies;

/**
 * Configures the trusted-proxy list from `config('security.trusted_proxies')`.
 *
 * WHY THIS IS NOT IN bootstrap/app.php, WHERE IT USED TO LIVE.
 *
 * `->withMiddleware(...)` registers its callback on `afterResolving(HttpKernel::class)`, which fires
 * when the kernel object is constructed — BEFORE `Kernel::handle()` runs the bootstrappers. Measured
 * on this branch, at that moment:
 *
 *     configLoaded = NO   (the `config` binding does not exist yet, cached or not)
 *     LoadEnvironmentVariables has not run, so .env has not been read
 *
 * Two consequences followed, and both were live:
 *
 * 1. `config('security.trusted_proxies')` there returns NULL. Moving the read to config() inside
 *    that closure — the obvious fix — would have produced an empty proxy list in every environment.
 * 2. The `env('TRUSTED_PROXIES')` that was there could only ever see the REAL PROCESS ENVIRONMENT.
 *    A value in a .env file was invisible to it. Measured: with `TRUSTED_PROXIES=*` present in
 *    .env, the resolved list was `[]` — both with the config cached and without.
 *
 * `[]` means trust no proxies, so behind a load balancer every request reports the balancer's IP and
 * every IP-keyed limiter — the login lockout, the OTP hourly budget, the checkout throttle — shares
 * a single bucket. Nothing fails loudly; the site serves normally with its rate limiting collapsed.
 *
 * The Docker Compose stack was not affected, because `env_file:` sets real process environment
 * variables and the base image's `/usr/local/etc/php-fpm.d/docker.conf` ships `clear_env = no`
 * (verified in `php:8.3-fpm-alpine`), so FPM workers inherit them. Every .env-on-disk deployment
 * was, including the bare-metal path in DEPLOYMENT_CHECKLIST.md.
 *
 * Running from a service provider's boot() fixes both: config is loaded by then, and providers boot
 * inside `Kernel::bootstrap()`, which completes before the middleware pipeline runs. `TrustProxies`
 * reads the static at request time, so setting it here is in time.
 */
final class TrustedEdgeConfigurator
{
    /**
     * Fail CLOSED. Trusting all proxies when the value is unset lets any client spoof
     * X-Forwarded-For and defeat every IP-keyed rate limiter, so an unset value trusts nothing.
     * `*` remains available, but only as an explicit, deliberate choice.
     */
    public static function apply(): void
    {
        $configured = trim((string) config('security.trusted_proxies', ''));

        TrustProxies::at(match (true) {
            $configured === '' => [],
            $configured === '*' => '*',
            default => array_values(array_filter(array_map('trim', explode(',', $configured)))),
        });
    }
}
