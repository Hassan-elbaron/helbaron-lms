<?php

use App\Console\Commands\ValidateEnvironment;
use Illuminate\Support\Facades\Artisan;

/**
 * C3 — `env:validate` must cover every payment gateway, not just Stripe.
 *
 * Two defects sat behind this. The obvious one: six of the seven supported gateways were never
 * checked, so an instance could be configured for Moyasar with no credentials at all and this
 * command would print "Environment OK". The subtle one: the Stripe check that DID exist read
 * `services.stripe.*`, while GatewayManager builds its adapter from `commerce.gateways.stripe.*` —
 * two resolution paths for one setting, the same shape of bug as the certificate-issuer inversion.
 * The keys happen to come from the same environment variables today, so the check passed for the
 * wrong reason; renaming either side would have silently unhooked it.
 *
 * Assertions are on OUTPUT, not exit code: `--production` also fails a test environment on
 * APP_DEBUG, session.secure and CORS, so the exit code cannot distinguish a gateway problem from
 * the ambient ones.
 */
function validateEnvironmentOutput(): string
{
    Artisan::call('env:validate', ['--production' => true]);

    return Artisan::output();
}

/**
 * Every credential a gateway needs, from the shipped requirement map.
 *
 * @return array<string, list<string|list<string>>>
 */
function gatewayRequirements(): array
{
    return (new ReflectionClass(ValidateEnvironment::class))->getConstant('GATEWAY_REQUIREMENTS');
}

function selectGateway(string $provider, array $credentials = []): void
{
    config(['commerce.payment.provider' => $provider]);

    // Blank every key the gateway declares, then set only what the case under test supplies.
    $blank = array_map(fn (): string => '', (array) config("commerce.gateways.{$provider}", []));

    config(["commerce.gateways.{$provider}" => array_merge($blank, $credentials)]);
}

/** Fully-credentialed config for a gateway: every required key filled with a placeholder. */
function credentialsFor(string $provider): array
{
    $filled = [];

    foreach (gatewayRequirements()[$provider] as $requirement) {
        $keys = is_array($requirement) ? $requirement : [$requirement];
        // For an any-of requirement, satisfying the FIRST alternative must be enough.
        $filled[$keys[0]] = 'set-for-test';
    }

    return $filled;
}

it('reports every missing credential for the selected gateway', function (string $provider): void {
    selectGateway($provider);

    $output = validateEnvironmentOutput();

    foreach (gatewayRequirements()[$provider] as $requirement) {
        $keys = is_array($requirement) ? $requirement : [$requirement];

        foreach ($keys as $key) {
            expect($output)->toContain(strtoupper($provider.'_'.$key));
        }
    }
})->with(['stripe', 'paymob', 'moyasar', 'hyperpay', 'tap', 'aps']);

it('reports nothing for a fully-credentialed gateway', function (string $provider): void {
    selectGateway($provider, credentialsFor($provider));

    $output = validateEnvironmentOutput();

    expect($output)->not->toContain(ucfirst($provider).' selected but');
})->with(['stripe', 'paymob', 'moyasar', 'hyperpay', 'tap', 'aps']);

/*
 * The negative half of the pair above. Without it, "reports nothing when credentialed" would also
 * pass on a command that had no gateway checks at all — which is precisely the state this replaces.
 */
it('does not complain about a gateway that is not selected', function (): void {
    selectGateway('moyasar', credentialsFor('moyasar'));
    selectGateway('stripe', credentialsFor('stripe'));
    config(['commerce.gateways.moyasar' => ['secret_key' => '', 'webhook_secret' => '', 'callback_url' => '']]);

    $output = validateEnvironmentOutput();

    expect($output)->not->toContain('MOYASAR_');
});

it('accepts either secret Paymob can verify a webhook with', function (string $key): void {
    selectGateway('paymob', [
        'api_key' => 'k', 'integration_id' => '1', 'iframe_id' => '2', $key => 'secret',
    ]);

    $output = validateEnvironmentOutput();

    expect($output)->not->toContain('Paymob selected but');
})->with(['hmac_secret', 'webhook_secret']);

it('names both alternatives when Paymob has neither', function (): void {
    selectGateway('paymob', ['api_key' => 'k', 'integration_id' => '1', 'iframe_id' => '2']);

    $output = validateEnvironmentOutput();

    expect($output)->toContain('PAYMOB_HMAC_SECRET')
        ->toContain('PAYMOB_WEBHOOK_SECRET');
});

it('asks for no credentials for the fake gateway', function (): void {
    selectGateway('fake');

    $output = validateEnvironmentOutput();

    expect($output)->not->toContain('Fake selected but');
});

it('rejects a provider no gateway implements', function (): void {
    config(['commerce.payment.provider' => 'square']);

    $output = validateEnvironmentOutput();

    // Checkout would throw InvalidArgumentException on the first order; better to hear it here.
    expect($output)->toContain("COMMERCE_PAYMENT_PROVIDER='square' is not a supported gateway");
});

/*
 * Coverage, stated as a test rather than as a comment: the requirement map must know about exactly
 * the gateways the application ships. Adding a seventh gateway to config/commerce.php without adding
 * it here would silently reintroduce the original defect for that gateway alone.
 */
it('covers every gateway the application configures', function (): void {
    $configured = array_keys((array) config('commerce.gateways'));
    $covered = array_keys(gatewayRequirements());

    sort($configured);
    sort($covered);

    expect($covered)->toBe($configured);
});

/*
 * The env-var names are DERIVED (provider_key, uppercased) rather than listed, so this asserts the
 * derivation still matches reality. A rename in config/commerce.php that broke the convention would
 * otherwise leave env:validate telling an operator to set a variable that does not exist.
 */
it('names environment variables that config/commerce.php actually reads', function (): void {
    $source = (string) file_get_contents(config_path('commerce.php'));

    foreach (gatewayRequirements() as $provider => $requirements) {
        foreach ($requirements as $requirement) {
            foreach (is_array($requirement) ? $requirement : [$requirement] as $key) {
                $name = strtoupper($provider.'_'.$key);

                expect($source)->toContain("env('{$name}')");
            }
        }
    }
});

/*
 * The original bug, guarded directly: the check must read the config GatewayManager consumes.
 * Credentials present under `commerce.gateways` and absent under `services` must satisfy it.
 */
it('reads the gateway config the payment manager actually uses', function (): void {
    selectGateway('stripe', credentialsFor('stripe'));
    config(['services.stripe.secret' => null, 'services.stripe.webhook_secret' => null]);

    expect(validateEnvironmentOutput())->not->toContain('Stripe selected but');
});
