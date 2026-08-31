<?php

use App\Platform\Branding\Adapters\BrandProfileAdapter;
use App\Platform\Branding\Models\BrandSetting;
use App\Platform\Shared\Branding\Contracts\BrandProfilePort;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A fresh instance must be branded for its own academy before an admin opens the branding screen.
 *
 * TWO LAYERS ARE TESTED SEPARATELY, ON PURPOSE.
 *
 *  1. config/branding.php resolves the BRAND_* keys. It is re-evaluated here against a controlled
 *     environment, because that is the only way to exercise the real fallback chain: the framework
 *     evaluates a config file exactly once per process, so a putenv() in a test can never change an
 *     already-resolved config value. A test that called putenv() and then asserted on
 *     config('branding.*') would pass no matter what the file did — it would measure nothing.
 *  2. BrandSetting / BrandProfile consume config('branding.*'). That is driven with config([...]).
 *
 * Assertions are on BEHAVIOUR, never on a literal brand string: pinning the vendor's name in a
 * product sold as one white-labelled instance per customer is exactly what white-labelling deletes.
 */
const BRAND_ENV_KEYS = [
    'APP_NAME', 'BRAND_NAME_EN', 'BRAND_NAME_AR', 'BRAND_COMPANY_NAME', 'BRAND_SUPPORT_EMAIL',
    'BRAND_SUPPORT_PHONE', 'BRAND_ADDRESS_EN', 'BRAND_ADDRESS_AR',
    'BRAND_EMAIL_FOOTER_EN', 'BRAND_EMAIL_FOOTER_AR',
    'BRAND_EMAIL_SIGNATURE_EN', 'BRAND_EMAIL_SIGNATURE_AR',
];

function setEnvKey(string $key, ?string $value): void
{
    if ($value === null) {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);

        return;
    }

    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

/**
 * Re-evaluate config/branding.php against a controlled environment.
 *
 * @param  array<string, string>  $env
 * @return array<string, mixed>
 */
function brandingConfigWith(array $env): array
{
    foreach (BRAND_ENV_KEYS as $k) {
        setEnvKey($k, null);
    }
    foreach ($env as $k => $v) {
        setEnvKey($k, $v);
    }

    try {
        return require base_path('config/branding.php');
    } finally {
        foreach (BRAND_ENV_KEYS as $k) {
            setEnvKey($k, null);
        }
    }
}

// -- Layer 1: the config file's own resolution ----------------------------------------------------

it('resolves a complete brand when no BRAND_ keys are set at all', function (): void {
    $c = brandingConfigWith(['APP_NAME' => 'Acme Academy']);

    expect($c['name']['en'])->toBe('Acme Academy')
        ->and($c['name']['ar'])->toBe('Acme Academy')
        ->and($c['company_name'])->toBe('Acme Academy')
        ->and($c['email']['footer']['en'])->toBe('Acme Academy')
        ->and($c['email']['signature']['en'])->toContain('Acme Academy');
});

/*
 * THE BUG. `.env.example` shipped these keys as `KEY=`, which is present-with-value-''. env()'s
 * default argument does not fire for '', so the fallback chain was dead and a fresh instance got a
 * blank Arabic brand name, a blank company name (which also feeds the certificate issuer) and blank
 * email footers.
 */
it('resolves a complete brand when the BRAND_ keys are present but EMPTY', function (): void {
    $c = brandingConfigWith([
        'APP_NAME' => 'Acme Academy',
        'BRAND_NAME_EN' => '', 'BRAND_NAME_AR' => '', 'BRAND_COMPANY_NAME' => '',
        'BRAND_EMAIL_FOOTER_EN' => '', 'BRAND_EMAIL_FOOTER_AR' => '',
        'BRAND_EMAIL_SIGNATURE_EN' => '', 'BRAND_EMAIL_SIGNATURE_AR' => '',
    ]);

    expect($c['name']['en'])->toBe('Acme Academy')
        ->and($c['name']['ar'])->toBe('Acme Academy')
        ->and($c['company_name'])->toBe('Acme Academy')
        ->and(trim($c['email']['footer']['en']))->not->toBe('')
        ->and(trim($c['email']['footer']['ar']))->not->toBe('')
        ->and(trim($c['email']['signature']['ar']))->not->toBe('');
});

it('lets an empty Arabic key inherit the English name rather than blanking the Arabic UI', function (): void {
    $c = brandingConfigWith(['BRAND_NAME_EN' => 'Acme Academy', 'BRAND_NAME_AR' => '']);

    expect($c['name']['ar'])->toBe('Acme Academy');
});

it('uses a genuinely configured Arabic name when one is given', function (): void {
    $c = brandingConfigWith(['BRAND_NAME_EN' => 'Acme Academy', 'BRAND_NAME_AR' => 'Akadimiyat Acme']);

    expect($c['name']['ar'])->toBe('Akadimiyat Acme')
        ->and($c['name']['en'])->toBe('Acme Academy');
});

it('lets an explicit company name override the brand name', function (): void {
    $c = brandingConfigWith(['BRAND_NAME_EN' => 'Acme Academy', 'BRAND_COMPANY_NAME' => 'Acme Holdings Ltd']);

    expect($c['name']['en'])->toBe('Acme Academy')
        ->and($c['company_name'])->toBe('Acme Holdings Ltd')
        // The footer follows the company name, not the brand name.
        ->and($c['email']['footer']['en'])->toBe('Acme Holdings Ltd');
});

// -- Layer 2: BrandSetting / BrandProfile consume the resolved config ------------------------------

it('builds the branding defaults from config rather than reading the environment directly', function (): void {
    config([
        'branding.name.en' => 'Acme Academy',
        'branding.name.ar' => 'Akadimiyat Acme',
        'branding.company_name' => 'Acme Holdings Ltd',
        'branding.email.footer.en' => 'Acme Holdings Ltd',
        'branding.email.signature.en' => 'The Acme Academy Team',
    ]);

    $defaults = BrandSetting::defaults();

    expect($defaults['identity']['brand_name']['en'])->toBe('Acme Academy')
        ->and($defaults['identity']['brand_name']['ar'])->toBe('Akadimiyat Acme')
        ->and($defaults['identity']['company_name'])->toBe('Acme Holdings Ltd')
        ->and($defaults['email']['footer']['en'])->toBe('Acme Holdings Ltd');
});

it('carries the configured brand onto the profile port every outbound surface reads', function (): void {
    config([
        'branding.name.en' => 'Acme Academy',
        'branding.company_name' => 'Acme Holdings Ltd',
        'branding.email.footer.en' => 'Acme Holdings Ltd',
        'branding.email.signature.en' => 'The Acme Academy Team',
    ]);

    $profile = (new BrandProfileAdapter)->profile('en');

    expect($profile->name)->toBe('Acme Academy')
        ->and($profile->companyName)->toBe('Acme Holdings Ltd')
        ->and($profile->emailFooter)->toBe('Acme Holdings Ltd')
        ->and($profile->emailSignature)->toContain('Acme Academy')
        ->and($profile->templateVariables()['brand'])->toBe('Acme Academy');
});

it('never returns an empty brand name from the port', function (): void {
    $profile = app(BrandProfilePort::class)->profile();

    expect(trim($profile->name))->not->toBe('')
        ->and(trim($profile->companyName))->not->toBe('')
        ->and(trim($profile->emailFooter))->not->toBe('');
});

it('resolves the port through the container without creating a settings row', function (): void {
    expect(BrandSetting::query()->count())->toBe(0);

    app(BrandProfilePort::class)->profile();

    expect(BrandSetting::query()->count())->toBe(0);
});
