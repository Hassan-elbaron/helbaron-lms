<?php

use App\Platform\Shared\Config\ConfigGuardScope;
use Illuminate\Contracts\Foundation\Application;

/**
 * C2 — which processes must refuse to start on an unsafe production configuration.
 *
 * The guard previously exempted the entire console (`runningInConsole()`), so a production queue
 * worker booted happily against a configuration the web container had already refused to serve. The
 * site would be down while Horizon kept draining the queue — charging cards, sending mail and
 * issuing certificates on a configuration nobody had accepted.
 *
 * Tested through a doubled Application rather than by booting one: the real guard runs inside an
 * `app->booted()` closure during bootstrap, which cannot be re-entered from a test. The decision is
 * the part with the bug in it, so the decision is what is asserted.
 */
function scopeFor(bool $production, bool $console, ?string $command = null): bool
{
    $app = Mockery::mock(Application::class);
    $app->shouldReceive('environment')->with('production')->andReturn($production);
    $app->shouldReceive('runningInConsole')->andReturn($console);
    $app->shouldReceive('runningConsoleCommand')
        ->andReturnUsing(fn (array $commands): bool => in_array($command, $commands, true));

    return ConfigGuardScope::appliesTo($app);
}

it('guards the production web path', function (): void {
    expect(scopeFor(production: true, console: false))->toBeTrue();
});

it('guards long-running production workers', function (string $command): void {
    expect(scopeFor(production: true, console: true, command: $command))->toBeTrue();
})->with(['horizon', 'horizon:work', 'queue:work', 'queue:listen', 'schedule:work', 'schedule:run', 'octane:start']);

/*
 * The exemption has to survive. A guard that also blocks config:validate, migrate and tinker makes a
 * bad configuration UNDIAGNOSABLE from the box it is on, which turns a five-minute fix into an
 * outage. These are the commands an operator reaches for once the guard has already fired.
 */
it('never blocks the tooling used to diagnose the problem', function (string $command): void {
    expect(scopeFor(production: true, console: true, command: $command))->toBeFalse();
})->with(['config:validate', 'env:validate', 'migrate', 'tinker', 'install:academy', 'identity:create-admin', 'about']);

it('does nothing outside production', function (): void {
    expect(scopeFor(production: false, console: false))->toBeFalse()
        ->and(scopeFor(production: false, console: true, command: 'horizon'))->toBeFalse();
});

/*
 * The regression this exists to prevent, stated as itself: the worker list must not be empty, and it
 * must not quietly acquire an operator command.
 */
it('lists workers and only workers', function (): void {
    expect(ConfigGuardScope::WORKER_COMMANDS)
        ->toContain('horizon', 'queue:work', 'schedule:work')
        ->not->toContain('migrate', 'config:validate', 'tinker', 'install:academy');
});
