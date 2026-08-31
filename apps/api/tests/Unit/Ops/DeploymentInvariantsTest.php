<?php

/**
 * C2 + C5 — the parts of fleet operations that live in shell and compose rather than in PHP.
 *
 * These are file assertions, which is a blunt instrument, so each one guards a defect that was
 * actually found and fixed rather than a style preference. Deleting any of these lines re-opens a
 * specific failure that shipped in this repository:
 *
 *  - the deploy migrated before it validated, so an unsafe configuration was discovered after the
 *    first irreversible step;
 *  - the deploy "warmed caches" in a `docker compose run --rm` container whose filesystem is thrown
 *    away, so production had never once run with a cached config despite every config file being
 *    written on the assumption that it did;
 *  - the scheduled backups were written into the deployment checkout, which the platform replaces on
 *    every deploy;
 *  - a failing backup loop kept running retention, deleting its way to zero backups in silence.
 */
function repoFile(string $relative): string
{
    $path = dirname(base_path(), 2).'/'.$relative;

    expect(file_exists($path))->toBeTrue("{$relative} is missing");

    return (string) file_get_contents($path);
}

it('validates the configuration before it migrates', function (): void {
    $deploy = repoFile('scripts/deploy.sh');

    $validate = strpos($deploy, 'artisan config:validate');
    $migrate = strpos($deploy, 'artisan migrate --force');

    expect($validate)->not->toBeFalse('deploy.sh does not run config:validate')
        ->and($migrate)->not->toBeFalse()
        ->and($validate)->toBeLessThan($migrate);
});

it('does not warm caches in a container it then throws away', function (): void {
    $deploy = repoFile('scripts/deploy.sh');

    // `docker compose run --rm` gets a fresh container whose writable layer is discarded on exit,
    // and the api service mounts no volume over bootstrap/cache. Anything cached there is unreadable
    // by every process that actually serves.
    expect($deploy)->not->toMatch('/run --rm[^\n]*artisan (config|route|event):cache/');
});

it('warms the caches in the container that will serve, via the image entrypoint', function (): void {
    $entrypoint = repoFile('apps/api/infra/php/docker-entrypoint.sh');

    expect($entrypoint)->toContain('artisan config:cache')
        ->toContain('artisan route:cache')
        ->toContain('artisan event:cache')
        // Without `exec` the shell stays as PID 1 and never forwards SIGTERM, so a rolling deploy
        // would wait out the full stop timeout on every container instead of shutting down cleanly.
        ->toContain('exec "$@"')
        // Fail closed: a container that cannot compile its config must not serve on a partial one.
        ->toContain('set -e');

    expect(repoFile('apps/api/Dockerfile'))
        ->toContain('ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]');
});

it('bakes the build commit into the image so /api/v1/version can report it', function (): void {
    expect(repoFile('apps/api/Dockerfile'))->toContain('ARG APP_BUILD_SHA');
    expect(repoFile('.github/workflows/ci.yml'))->toContain('APP_BUILD_SHA=${{ github.sha }}');
});

it('keeps backups out of the deployment checkout', function (): void {
    $compose = repoFile('docker-compose.prod.yml');

    // `./backups` is inside the directory the platform replaces on every deploy.
    expect($compose)->not->toContain('- ./backups:')
        ->and($compose)->toContain('${BACKUP_TARGET:-helbaron-dbbackups}:/backups');

    expect(repoFile('scripts/backup.sh'))->not->toContain('BACKUP_DIR:-backups}');
});

it('makes a failing backup visible instead of silent', function (): void {
    $compose = repoFile('docker-compose.prod.yml');

    expect($compose)
        // A sentinel the healthcheck can see, so the container reports unhealthy rather than
        // sleeping quietly between broken attempts.
        ->toContain('BACKUP_FAILING')
        ->toContain('.last_success')
        // Size check: a pipeline can exit 0 having written a truncated dump.
        ->toContain('BACKUP_MIN_BYTES');
});

it('never prunes old backups on the failure path', function (): void {
    $compose = repoFile('docker-compose.prod.yml');

    // Retention must sit inside the success branch. Pruning while backups are failing is how a
    // broken job deletes its way to zero backups without anyone being told.
    $success = strpos($compose, 'rm -f /backups/BACKUP_FAILING');
    $prune = strpos($compose, "find /backups -name 'db-*.sql.gz' -mtime");
    $failure = strpos($compose, 'FAILED at $$ts" >> /backups/BACKUP_FAILING');

    expect($success)->not->toBeFalse()
        ->and($prune)->not->toBeFalse()
        ->and($failure)->not->toBeFalse()
        ->and($prune)->toBeGreaterThan($success)
        ->and($prune)->toBeLessThan($failure);
});
