<?php

use App\Domains\Certification\Models\CertificateSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Issuer precedence: explicit override -> instance brand -> last-resort fallback.
 *
 * `CertificateSetting::defaultIssuerName()` used to read `env('CERTIFICATION_ISSUER')` directly at
 * request time. Two defects in one line:
 *
 *  1. An env() read from application code returns null once `config:cache` has run, unless the value
 *     also reaches the process as a real environment variable. An operator's explicit issuer could
 *     therefore be silently ignored, and the certificate would quietly fall back to the brand.
 *  2. It duplicated a key `config/certification.php` already resolved, so the two could drift.
 *
 * Both halves of the precedence are asserted, so the test fails if the override stops winning AND if
 * the brand fallback stops working — not just one of them.
 */
it('prefers an explicitly configured issuer over the instance brand', function (): void {
    config([
        'certification.issuer.override' => 'Acme Awarding Body',
        'branding.name.en' => 'Acme Academy',
        'branding.company_name' => 'Acme Holdings Ltd',
    ]);

    expect(CertificateSetting::current()->issuer_name)->toBe('Acme Awarding Body');
});

it('falls back to the brand company name when no issuer is configured', function (): void {
    config([
        'certification.issuer.override' => '',
        'branding.name.en' => 'Acme Academy',
        'branding.company_name' => 'Acme Holdings Ltd',
    ]);

    // The company name is the legal entity that awards the certificate, so it outranks the brand.
    expect(CertificateSetting::current()->issuer_name)->toBe('Acme Holdings Ltd');
});

it('falls back to the brand name when no company name is set either', function (): void {
    config([
        'certification.issuer.override' => '',
        'branding.name.en' => 'Acme Academy',
        'branding.company_name' => '',
    ]);

    expect(CertificateSetting::current()->issuer_name)->toBe('Acme Academy');
});

it('never issues a certificate with an empty issuer', function (): void {
    config([
        'certification.issuer.override' => '',
        'branding.name.en' => '',
        'branding.company_name' => '',
    ]);

    // A blank issuer would print an unattributed certificate. The last-resort config value exists to
    // make that impossible.
    expect(trim((string) CertificateSetting::current()->issuer_name))->not->toBe('');
});

it('resolves the issuer override from config rather than reading the environment', function (): void {
    // The regression guard for the actual fix. Setting ONLY the environment variable — with the
    // config key empty, which is the shape `config:cache` leaves behind — must not reach the issuer.
    putenv('CERTIFICATION_ISSUER=Should Not Win');
    $_ENV['CERTIFICATION_ISSUER'] = 'Should Not Win';

    config([
        'certification.issuer.override' => '',
        'branding.name.en' => 'Acme Academy',
        'branding.company_name' => 'Acme Holdings Ltd',
    ]);

    $issuer = CertificateSetting::current()->issuer_name;

    putenv('CERTIFICATION_ISSUER');
    unset($_ENV['CERTIFICATION_ISSUER']);

    expect($issuer)->toBe('Acme Holdings Ltd')
        ->and($issuer)->not->toBe('Should Not Win');
});
