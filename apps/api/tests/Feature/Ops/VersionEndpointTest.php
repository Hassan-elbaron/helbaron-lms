<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * C4 — GET /api/v1/version: which build is this academy running, and did its migrations apply?
 *
 * `config('app.version')` did not exist. Laravel 12 ships no config/app.php, and nothing had added
 * one, so the two places that read the key both fell through to a hardcoded '1.0.0-rc.1' literal —
 * every instance in the fleet reported the same version forever, whatever it was actually running.
 */
it('reports build provenance and schema state', function (): void {
    $response = $this->getJson('/api/v1/version');

    $response->assertOk()->assertJsonStructure([
        'service', 'version', 'commit', 'built_at', 'environment',
        'migrations' => ['state', 'applied', 'pending', 'up_to_date'],
        'time',
    ]);
});

it('reports the configured version rather than a literal', function (): void {
    config(['app.version' => '2.4.0', 'app.build_sha' => 'deadbeefcafe']);

    $this->getJson('/api/v1/version')
        ->assertJsonPath('version', '2.4.0')
        ->assertJsonPath('commit', 'deadbeefcafe');
});

/*
 * The defect itself. Without config/app.php this key resolves to NULL, and every caller silently
 * falls back to a literal that has no relationship to the deployed code.
 */
it('resolves app.version from configuration', function (): void {
    expect(config('app.version'))->not->toBeNull()->not->toBe('');
});

it('reports the same version on the health endpoint', function (): void {
    config(['app.version' => '2.4.0']);

    $this->getJson('/api/v1/health')->assertJsonPath('version', '2.4.0');
});

it('reports the schema as up to date on a migrated database', function (): void {
    $this->getJson('/api/v1/version')
        ->assertJsonPath('migrations.state', 'ok')
        ->assertJsonPath('migrations.pending', 0)
        ->assertJsonPath('migrations.up_to_date', true);
});

it('counts every registered migration path, not just database/migrations', function (): void {
    // Domain modules register their own paths via loadMigrationsFrom(); a count that only saw the
    // default directory would under-report by hundreds and call a half-migrated instance current.
    $applied = $this->getJson('/api/v1/version')->json('migrations.applied');

    expect($applied)->toBeGreaterThan(count(glob(database_path('migrations/*.php')) ?: []));
});

/*
 * This endpoint is PUBLIC on every customer academy, so nothing in its payload may name the vendor.
 * The first version of it returned the newest applied migration, which on this branch is
 * `2026_08_30_000400_rename_why_helbaron_homepage_section_key` — the vendor's name on a public URL of
 * every instance in the fleet. Migration filenames are free text and can never be trusted to be
 * white-label, so the endpoint reports counts and no names at all.
 */
it('carries no vendor name anywhere in the payload', function (): void {
    $body = strtolower((string) $this->getJson('/api/v1/version')->getContent());

    expect($body)->not->toContain('helbaron');
});

it('reports migration counts and never migration names', function (): void {
    $migrations = $this->getJson('/api/v1/version')->json('migrations');

    expect(array_keys($migrations))->toBe(['state', 'applied', 'pending', 'up_to_date']);
});

/*
 * config/app.php is a PARTIAL file: Illuminate merges it over the framework's own app config. If
 * that merge ever stopped happening, the file would displace the framework defaults and take down
 * encryption, providers and localisation with it — a much larger failure than the one it fixes.
 */
it('keeps the framework application defaults intact', function (): void {
    expect(config('app.cipher'))->not->toBeEmpty()
        ->and(config('app.providers'))->toBeArray()->not->toBeEmpty()
        ->and(config('app.locale'))->not->toBeEmpty()
        ->and(config('app.timezone'))->not->toBeNull();
});
