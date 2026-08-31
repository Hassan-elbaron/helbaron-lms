<?php

use App\Platform\Shared\Support\Env;

/**
 * The present-but-empty environment key.
 *
 * `env('KEY', $default)` returns the default only when the key is ABSENT. `.env.example` shipped a
 * dozen optional keys as `KEY=`, which is present with the value '' — so every fallback chain built
 * on env()'s second argument was dead, and instances silently received empty brand names, an empty
 * company name (which also feeds the certificate issuer) and an empty seeded admin password.
 */
afterEach(function (): void {
    foreach (['ENVT_ABSENT', 'ENVT_EMPTY', 'ENVT_SPACES', 'ENVT_SET', 'ENVT_FALSE', 'ENVT_ZERO'] as $k) {
        putenv($k);
        unset($_ENV[$k], $_SERVER[$k]);
    }
});

function putEnvValue(string $key, string $value): void
{
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

it('falls back when the key is absent', function (): void {
    expect(Env::string('ENVT_ABSENT', 'fallback'))->toBe('fallback');
});

/*
 * The actual bug. This is the case plain env() gets wrong, and the reason this helper exists.
 */
it('falls back when the key is present but empty', function (): void {
    putEnvValue('ENVT_EMPTY', '');

    // Proof the fix is load-bearing: the built-in helper returns '' for exactly this input.
    expect(env('ENVT_EMPTY', 'fallback'))->toBe('')
        ->and(Env::string('ENVT_EMPTY', 'fallback'))->toBe('fallback');
});

it('falls back when the key holds only whitespace', function (): void {
    putEnvValue('ENVT_SPACES', '   ');

    expect(Env::string('ENVT_SPACES', 'fallback'))->toBe('fallback');
});

it('uses the configured value when one is genuinely set', function (): void {
    putEnvValue('ENVT_SET', 'Acme Academy');

    expect(Env::string('ENVT_SET', 'fallback'))->toBe('Acme Academy');
});

it('evaluates a closure fallback only when it is needed', function (): void {
    $calls = 0;
    $fallback = function () use (&$calls): string {
        $calls++;

        return 'computed';
    };

    putEnvValue('ENVT_SET', 'Acme');
    expect(Env::string('ENVT_SET', $fallback))->toBe('Acme')
        ->and($calls)->toBe(0);

    expect(Env::string('ENVT_ABSENT', $fallback))->toBe('computed')
        ->and($calls)->toBe(1);
});

/*
 * A boolean or numeric zero is a real setting, not an absent one. Treating them as blank would make
 * LEARNING_PLAYBACK_ALLOW_FAKE=false silently fall through to a default of true — turning a
 * fail-closed production guard into a fail-open one.
 */
it('does not treat false or zero as absent', function (): void {
    putEnvValue('ENVT_FALSE', 'false');
    putEnvValue('ENVT_ZERO', '0');

    expect(Env::orFallback('ENVT_FALSE', 'fallback'))->toBeFalse()
        ->and(Env::orFallback('ENVT_ZERO', 'fallback'))->toBe('0');
});
