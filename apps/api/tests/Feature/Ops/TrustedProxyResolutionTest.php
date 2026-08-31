<?php

use App\Platform\Shared\Http\TrustedEdgeConfigurator;
use Illuminate\Http\Middleware\TrustHosts;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;

/**
 * The trusted-proxy list must survive `config:cache`, and it must come from configuration.
 *
 * THE DEFECT THIS GUARDS. `bootstrap/app.php` used to call `env('TRUSTED_PROXIES')` inside the
 * `->withMiddleware()` closure. That closure runs on `afterResolving(HttpKernel::class)`, which is
 * BEFORE the framework's bootstrappers: `config` is not bound and `LoadEnvironmentVariables` has not
 * read the .env file. So it could only ever see a REAL PROCESS environment variable. Measured on this
 * branch, with `TRUSTED_PROXIES=*` sitting in .env, the resolved list was `[]` — cached and uncached
 * alike.
 *
 * `[]` means trust nothing, so behind a load balancer every request reports the balancer's address
 * and every IP-keyed limiter — login lockout, the OTP hourly budget, the checkout throttle — shares
 * one bucket. One user's failures exhaust everybody's allowance. Nothing throws; the site serves
 * normally with its rate limiting collapsed, which is the worst shape a defect can take.
 *
 * The Compose stack was insulated by accident: `env_file:` sets real process variables and the base
 * image's docker.conf ships `clear_env = no`. Every .env-on-disk deployment was not.
 *
 * The tests below assert the BEHAVIOUR (a forwarded address actually survives), not just that a
 * setter was called — the setter was being called before, with the wrong value.
 */
beforeEach(function (): void {
    $this->originalProxies = (new ReflectionClass(TrustProxies::class))
        ->getStaticPropertyValue('alwaysTrustProxies');
});

afterEach(function (): void {
    // TrustProxies keeps its list in a static, so a test that changes it would leak into every test
    // that follows in the same process.
    TrustProxies::at($this->originalProxies ?? []);
});

function resolvedProxies(): mixed
{
    return (new ReflectionClass(TrustProxies::class))->getStaticPropertyValue('alwaysTrustProxies');
}

it('resolves the proxy list from configuration', function (): void {
    config(['security.trusted_proxies' => '10.0.0.0/8,192.168.0.1']);

    TrustedEdgeConfigurator::apply();

    expect(resolvedProxies())->toBe(['10.0.0.0/8', '192.168.0.1']);
});

/*
 * The one the reviewer asked for, stated as behaviour: a configured proxy list must be non-empty
 * after the application has booted. Config is what survives `config:cache` — env() read before the
 * bootstrappers is what did not.
 */
it('is never empty when the configuration names a proxy', function (string $configured): void {
    config(['security.trusted_proxies' => $configured]);

    TrustedEdgeConfigurator::apply();

    expect(resolvedProxies())->not->toBe([])->not->toBeEmpty();
})->with(['*', '10.0.0.0/8', '10.0.0.0/8,172.16.0.0/12']);

it('fails closed when nothing is configured', function (?string $configured): void {
    config(['security.trusted_proxies' => $configured]);

    TrustedEdgeConfigurator::apply();

    // Trusting every proxy by default would let any client spoof X-Forwarded-For and defeat every
    // IP-keyed limiter. Unset must mean trust nothing, not trust everything.
    expect(resolvedProxies())->toBe([]);
})->with(['empty' => [''], 'whitespace' => ['   '], 'null' => [null]]);

/*
 * End to end. Without this, everything above would also pass on an application that set the static
 * correctly and never consulted it.
 */
it('honours X-Forwarded-For from a configured proxy', function (): void {
    config(['security.trusted_proxies' => '192.168.10.5']);
    TrustedEdgeConfigurator::apply();

    $seen = null;
    Route::get('/__proxy-probe', function (Request $request) use (&$seen): string {
        $seen = $request->ip();

        return 'ok';
    });

    $this->call('GET', '/__proxy-probe', server: [
        'REMOTE_ADDR' => '192.168.10.5',
        'HTTP_X_FORWARDED_FOR' => '203.0.113.9',
    ]);

    expect($seen)->toBe('203.0.113.9');
});

it('ignores X-Forwarded-For from an untrusted source', function (): void {
    config(['security.trusted_proxies' => '192.168.10.5']);
    TrustedEdgeConfigurator::apply();

    $seen = null;
    Route::get('/__proxy-probe-2', function (Request $request) use (&$seen): string {
        $seen = $request->ip();

        return 'ok';
    });

    // A client that is not the load balancer must not be able to choose its own rate-limit key.
    $this->call('GET', '/__proxy-probe-2', server: [
        'REMOTE_ADDR' => '198.51.100.7',
        'HTTP_X_FORWARDED_FOR' => '203.0.113.9',
    ]);

    expect($seen)->toBe('198.51.100.7');
});

/*
 * The host allow-list moved to config() too, but for a different reason: `trustHosts(at:)` accepts a
 * CALLABLE, which TrustHosts invokes per request (TrustHosts::hosts()), by which time config is
 * loaded. So that one could stay in bootstrap/app.php — but it was reading env(), which in a lazy
 * callable is reached after LoadEnvironmentVariables and so DID see .env. Two neighbouring lines with
 * different resolution semantics and no way to tell them apart by reading them; both now read config.
 *
 * Asserted through the middleware rather than through a request: TrustHosts deliberately does not
 * enforce while `runningUnitTests()`, so a request-level test would pass no matter what.
 */
it('resolves the host allow-list from configuration', function (): void {
    config(['security.trusted_hosts' => 'academy.test, www.academy.test']);

    $hosts = (new TrustHosts(app()))->hosts();

    expect($hosts)->toContain('academy.test')->toContain('www.academy.test');
});

it('trusts no explicit host when none is configured', function (): void {
    config(['security.trusted_hosts' => '']);

    $hosts = array_filter(
        (new TrustHosts(app()))->hosts(),
        // The framework always appends a pattern for the application URL's own subdomains; only the
        // configured entries are under test here.
        fn (string $host): bool => ! str_starts_with($host, '^'),
    );

    expect($hosts)->toBe([]);
});

/*
 * The structural half. Anything read with env() inside bootstrap/app.php runs before
 * LoadEnvironmentVariables and therefore cannot see a .env file at all — the value is silently
 * whatever the process environment happens to hold, which on most deployments is nothing. There is
 * no correct use of env() in that file.
 */
it('makes no env() call in bootstrap/app.php', function (): void {
    expect(phpCodeWithoutComments(base_path('bootstrap/app.php')))->not->toMatch('/\benv\s*\(/');
});

it('validates the same key the application applies', function (): void {
    // env:validate read the environment variable directly, calling it a third resolution path for
    // one setting — and it defaulted to the opposite of what the application actually did.
    $command = phpCodeWithoutComments(app_path('Console/Commands/ValidateEnvironment.php'));

    expect($command)->toContain("config('security.trusted_proxies'")
        ->and($command)->not->toMatch('/\benv\s*\(\s*.TRUSTED_PROXIES/');
});

/**
 * Source with comments and docblocks removed.
 *
 * Both assertions above search for a pattern that these files legitimately DESCRIBE in prose — they
 * document the defect being guarded against. Tokenising rather than regexing the raw text keeps the
 * guard about code and lets the explanation stay next to it.
 */
function phpCodeWithoutComments(string $path): string
{
    $code = '';

    foreach (token_get_all((string) file_get_contents($path)) as $token) {
        if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }

        $code .= is_array($token) ? $token[1] : $token;
    }

    return $code;
}
