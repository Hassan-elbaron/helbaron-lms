<?php

use Symfony\Component\Yaml\Yaml;

/**
 * D4 — the production compose stack. Nine defects, each of which fails quietly rather than loudly.
 *
 * These are file assertions, which is a blunt instrument, so every one guards a specific defect that
 * shipped in this repository rather than a preference. The stack cannot be booted in CI, so the
 * alternative to asserting its shape is not asserting it at all.
 */
function stackFile(string $relative): string
{
    $path = dirname(base_path(), 2).'/'.$relative;

    expect(file_exists($path))->toBeTrue("{$relative} is missing");

    return (string) file_get_contents($path);
}

/** @return array<string, mixed> */
function stack(): array
{
    // Parsed, not grepped: `${VAR:-default}` interpolation is irrelevant to structure, and a YAML
    // parse also proves the file is still valid after every edit below.
    return (array) Yaml::parse(stackFile('docker-compose.prod.yml'));
}

it('addresses upstreams by compose service name', function (): void {
    // Comments stripped: both files legitimately DESCRIBE the old identifier in prose explaining
    // why it is gone. The guard is about directives, not documentation.
    $compose = withoutHashComments(stackFile('docker-compose.prod.yml'));
    $nginx = withoutHashComments(stackFile('infra/nginx/nginx.conf'));

    // `lms-h-sbvbdl-*` is a Dokploy-generated project id that was hardcoded in BOTH files and had
    // to agree. Deploying the same repository under any other project name resolved nothing and
    // returned 502 with no indication why.
    expect($compose)->not->toContain('lms-h-sbvbdl')
        ->and($nginx)->not->toContain('lms-h-sbvbdl')
        ->and($nginx)->toContain('server api:9000')
        ->and($nginx)->toContain('server web:3000');
});

it('does not hand the application secret set to the database containers', function (): void {
    $services = stack()['services'];

    // env_file: [./.env] injected APP_KEY, every payment gateway secret and OPENAI_API_KEY into
    // containers that need three or four database variables.
    foreach (['postgres', 'db-backup'] as $name) {
        expect(array_key_exists('env_file', $services[$name]))
            ->toBeFalse("{$name} still loads the whole .env");
    }

    // The application containers legitimately need it.
    expect(array_key_exists('env_file', $services['api']))->toBeTrue();
});

it('publishes the plaintext origin on loopback only', function (): void {
    $ports = stack()['services']['nginx']['ports'];

    // "8080:80" binds 0.0.0.0 — the plaintext origin reachable from the internet, bypassing the TLS
    // terminator, its HSTS and its WAF.
    foreach ($ports as $mapping) {
        expect($mapping)->toStartWith('127.0.0.1:');
    }
});

it('requires a password on redis', function (): void {
    $redis = stack()['services']['redis'];
    $command = implode(' ', (array) $redis['command']);

    // Redis holds sessions, the cache and the entire queue. Unauthenticated, anything on the compose
    // network can read sessions and enqueue jobs the workers will run.
    expect($command)->toContain('--requirepass')
        ->and($command)->toContain('REDIS_PASSWORD');

    // A default password is the same as no password: the value must be required.
    expect(stackFile('docker-compose.prod.yml'))->toContain('REDIS_PASSWORD:?');
});

it('checks the application in the api healthcheck, not just the socket', function (): void {
    $test = implode(' ', (array) stack()['services']['api']['healthcheck']['test']);

    // The old probe asked FPM for /ping. `ping.path` is a pool setting enabled nowhere in this repo,
    // so cgi-fcgi received "Primary script unknown" and exited 0 — a wedged worker pool reported
    // healthy, and `web` starts on `api: condition: service_healthy`.
    expect($test)->not->toContain('SCRIPT_FILENAME=/ping')
        ->and($test)->toContain('fpm-healthcheck.sh');

    // The script must assert on the BODY. An exit code only proves that something answered.
    expect(stackFile('apps/api/infra/php/fpm-healthcheck.sh'))->toContain('"status":"ok"');
});

it('pins nginx to a patch version that supports resolve in an upstream', function (): void {
    $dockerfile = stackFile('infra/nginx/Dockerfile');

    // nginx.conf uses `server ... resolve;` inside upstream{}, which open-source nginx supports only
    // from 1.27.3. The floating `1.27-alpine` tag was free to resolve to 1.27.0, where the config
    // fails to load and the proxy does not start.
    expect($dockerfile)->not->toContain('FROM nginx:1.27-alpine');

    preg_match('/FROM nginx:(\d+)\.(\d+)\.(\d+)/', $dockerfile, $m);

    expect($m)->not->toBeEmpty('nginx must be pinned to an exact patch version');
    expect(version_compare("{$m[1]}.{$m[2]}.{$m[3]}", '1.27.3', '>='))->toBeTrue();
});

it('caps every container log and gives every container a limit', function (): void {
    $services = stack()['services'];

    foreach ($services as $name => $service) {
        // Docker's json-file driver has no default size cap: one chatty container fills the host
        // disk and takes Postgres down with it.
        expect(array_key_exists('logging', $service))->toBeTrue("{$name} has no log rotation");
        expect($service['logging']['options']['max-size'] ?? null)->not->toBeNull();
    }

    // Every long-lived service needs a ceiling; `migrate` runs once and exits.
    foreach (array_diff(array_keys($services), ['migrate']) as $name) {
        expect(array_key_exists('deploy', $services[$name]))
            ->toBeTrue("{$name} has no resource limit");
    }
});

it('ships filament assets with the php that references them', function (): void {
    $nginxDockerfile = stackFile('infra/nginx/Dockerfile');
    $services = stack()['services'];

    // Baked into the NGINX image, the admin panel's CSS/JS sat on a different release cadence from
    // the PHP: `composer update filament/filament` without an nginx rebuild shipped STALE assets —
    // not a 404, so nothing failed and nobody noticed.
    expect($nginxDockerfile)->not->toContain('COPY apps/api/public/css/filament');

    $apiVolumes = implode(' ', (array) $services['api']['volumes']);
    $nginxVolumes = implode(' ', (array) $services['nginx']['volumes']);

    expect($apiVolumes)->toContain('helbaron-filament-assets')
        ->and($nginxVolumes)->toContain('helbaron-filament-assets')
        // nginx only reads them.
        ->and($nginxVolumes)->toContain(':ro');

    // And the API container must actually populate the volume, or the panel is simply unstyled.
    expect(stackFile('apps/api/infra/php/docker-entrypoint.sh'))
        ->toContain('publish_filament_assets');
});

it('defaults its image tags to the version it claims to be', function (): void {
    $version = trim(stackFile('VERSION'));
    $compose = stackFile('docker-compose.prod.yml');

    // The tags said 1.0.0-rc.1 while VERSION said 1.0.0-rc.2, so an on-host build produced an image
    // whose name did not match the release it came from.
    expect($compose)->toContain('helbaron-api:'.$version)
        ->and($compose)->not->toContain('helbaron-api:1.0.0-rc.1');

    expect(stackFile('scripts/deploy.sh'))->toContain('helbaron-api:'.$version);
});

/** Config text with `#` comment lines removed. */
function withoutHashComments(string $text): string
{
    $lines = preg_split('/\R/', $text) ?: [];

    return implode("\n", array_filter(
        $lines,
        fn (string $line): bool => ! str_starts_with(ltrim($line), '#'),
    ));
}
