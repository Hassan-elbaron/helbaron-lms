<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Pre-flight production config validation. Fails fast if required settings are missing or unsafe
 * for a production release. Read-only: touches no data, sends nothing.
 *
 * Usage: php artisan env:validate  (exit 0 = ok, 1 = problems found)
 */
class ValidateEnvironment extends Command
{
    protected $signature = 'env:validate {--production : Apply strict production checks}';

    protected $description = 'Validate environment + config for a production release';

    /**
     * What each payment gateway must have before it can take a real payment.
     *
     * Previously only Stripe was checked — and it was checked at `services.stripe.*`, which is NOT
     * the config GatewayManager reads. Six of the seven supported gateways could therefore be
     * selected with no credentials at all and this command would report "Environment OK"; the
     * instance would fail on the customer's first checkout instead of on the operator's deploy.
     * Every entry below is a key under `commerce.gateways.<provider>` — the same array
     * GatewayManager hands to the adapter — and every one of them is read by that adapter.
     *
     * A nested list means ANY ONE of those keys satisfies the requirement (Paymob verifies webhooks
     * with hmac_secret and falls back to webhook_secret, so demanding both would be a false failure).
     *
     * base_url / api_url / sha_type / language are deliberately absent: they ship working defaults.
     *
     * @var array<string, list<string|list<string>>>
     */
    private const GATEWAY_REQUIREMENTS = [
        'fake' => [],
        'stripe' => ['secret', 'webhook_secret'],
        'paymob' => ['api_key', 'integration_id', 'iframe_id', ['hmac_secret', 'webhook_secret']],
        'moyasar' => ['secret_key', 'webhook_secret', 'callback_url'],
        'hyperpay' => ['access_token', 'entity_id', 'webhook_secret', 'hosted_url'],
        'tap' => ['secret_key', 'webhook_secret', 'redirect_url'],
        'aps' => ['access_code', 'merchant_identifier', 'request_phrase', 'response_phrase', 'return_url'],
    ];

    public function handle(): int
    {
        $prod = $this->option('production') || app()->isProduction();
        $errors = [];
        $warn = [];

        // Always-required.
        foreach (['APP_KEY' => config('app.key'), 'DB connection' => config('database.default')] as $k => $v) {
            if (blank($v)) {
                $errors[] = "Missing {$k}";
            }
        }

        if ($prod) {
            if (config('app.debug')) {
                $errors[] = 'APP_DEBUG must be false in production';
            }
            if (config('app.env') !== 'production') {
                $warn[] = "APP_ENV is '".config('app.env')."' (expected 'production')";
            }
            if (! config('session.secure')) {
                $errors[] = 'SESSION_SECURE_COOKIE must be true over HTTPS';
            }
            if (in_array('*', (array) config('cors.allowed_origins'), true) || config('cors.allowed_origins') === []) {
                $errors[] = 'CORS allowed_origins must be an explicit allow-list (never *)';
            }
            if (config('logging.default') !== 'json' && ! in_array('json', (array) config('logging.channels.stack.channels', []), true)) {
                $warn[] = "LOG_CHANNEL is '".config('logging.default')."' (recommend 'json' in production)";
            }
            // Cache + queue must be shared/async backends in production. `array` cache is per-process
            // (breaks rate limiting, analytics caching and any multi-instance deploy); `sync` queue
            // runs every job inline in the request (defeats notifications, exports, fan-out).
            if (config('cache.default') === 'array') {
                $errors[] = 'CACHE_STORE is "array" (per-process, not shared) — use redis in production';
            }
            if (config('queue.default') === 'sync') {
                $errors[] = 'QUEUE_CONNECTION is "sync" (jobs run inline) — use redis in production';
            }
            // Trusted proxies, read from the SAME config key TrustedEdgeConfigurator applies. This
            // used to read env('TRUSTED_PROXIES', '*') directly, "because it is applied inline in
            // bootstrap/app.php with no config key" — which was true of the old code and made this a
            // third resolution path for one setting, defaulting to the opposite of what the app did.
            $proxies = trim((string) config('security.trusted_proxies', ''));
            if ($proxies === '') {
                $errors[] = 'TRUSTED_PROXIES is not set — every request behind a load balancer reports the balancer\'s IP, so all IP-keyed rate limits (login lockout, OTP budget, checkout) share one bucket';
            } elseif ($proxies === '*') {
                $warn[] = 'TRUSTED_PROXIES trusts all proxies ("*") — scope it to your load balancer where possible';
            }
            // Payment gateway credentials for whichever gateway is actually selected.
            foreach ($this->paymentGatewayProblems() as $problem) {
                $errors[] = $problem;
            }

            if (config('learning.playback.provider') === 'mux' && blank(config('services.mux.signing_key'))) {
                $errors[] = 'Mux selected but MUX_SIGNING_KEY is empty';
            }
            foreach (['mail' => 'mailgun', 'sms' => 'twilio', 'push' => 'firebase'] as $ch => $real) {
                if (config("notifications.providers.{$ch}") === $real) {
                    $warn[] = "Notifications {$ch} uses real provider ({$real}) — confirm its secrets are set";
                }
            }
        }

        // Feature flags sanity.
        $flags = (array) config('features.flags', []);
        $this->line('Feature flags: '.($flags === [] ? 'none defined' : implode(', ', array_keys($flags))));

        foreach ($warn as $w) {
            $this->warn('WARN: '.$w);
        }
        foreach ($errors as $e) {
            $this->error('FAIL: '.$e);
        }

        if ($errors !== []) {
            $this->newLine();
            $this->error(count($errors).' problem(s) must be fixed before release.');

            return self::FAILURE;
        }

        $this->info('Environment OK'.($prod ? ' (production checks passed)' : '').'.');

        return self::SUCCESS;
    }

    /**
     * Problems with the SELECTED payment gateway's credentials. Only the selected gateway is
     * checked: an instance that sells in Saudi Arabia through Moyasar should not be blocked from
     * deploying because it has no Stripe keys.
     *
     * Names the environment variable, never the value.
     *
     * @return list<string>
     */
    private function paymentGatewayProblems(): array
    {
        $provider = (string) config('commerce.payment.provider', 'fake');

        if (! array_key_exists($provider, self::GATEWAY_REQUIREMENTS)) {
            return [
                "COMMERCE_PAYMENT_PROVIDER='{$provider}' is not a supported gateway (".
                implode(', ', array_keys(self::GATEWAY_REQUIREMENTS)).') — checkout would throw on the first order',
            ];
        }

        if ($provider === 'fake') {
            // Selecting the stub is a configuration decision guarded elsewhere (config:validate
            // refuses it in production unless COMMERCE_ALLOW_FAKE_GATEWAY is set). There are no
            // credentials to check, so re-reporting it here would only duplicate that message.
            return [];
        }

        $problems = [];

        foreach (self::GATEWAY_REQUIREMENTS[$provider] as $requirement) {
            $alternatives = is_array($requirement) ? $requirement : [$requirement];

            $satisfied = false;
            foreach ($alternatives as $key) {
                if (filled(config("commerce.gateways.{$provider}.{$key}"))) {
                    $satisfied = true;
                    break;
                }
            }

            if (! $satisfied) {
                $names = array_map(fn (string $key): string => self::envName($provider, $key), $alternatives);

                $problems[] = count($names) === 1
                    ? ucfirst($provider).' selected but '.$names[0].' is empty'
                    : ucfirst($provider).' selected but none of '.implode(' / ', $names).' is set';
            }
        }

        return $problems;
    }

    /**
     * The environment variable behind a gateway config key.
     *
     * config/commerce.php maps these one-for-one and uppercase (`aps.access_code` <-
     * APS_ACCESS_CODE), so the name is derived rather than duplicated in a second table that could
     * drift out of step with the first. PaymentGatewayValidationTest asserts every derived name
     * really appears in config/commerce.php, so a future rename cannot leave this printing an
     * environment variable that does not exist.
     */
    private static function envName(string $provider, string $key): string
    {
        return strtoupper($provider.'_'.$key);
    }
}
