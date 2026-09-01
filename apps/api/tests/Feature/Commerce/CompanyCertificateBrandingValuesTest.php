<?php

use App\Contexts\Commerce\Enums\CompanyCertificateBranding;
use App\Contexts\Commerce\Models\Product;
use App\Domains\Certification\Models\Certificate;
use App\Platform\Shared\Branding\Contracts\BrandProfilePort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * B2 — the vendor's name is out of the persisted data.
 *
 * `company_certificate_branding` stored `helbaron_only` / `company_logo_and_helbaron` as COLUMN
 * VALUES in two tables, with `helbaron_only` as the `products` column default. In a product sold as
 * one deployment per academy, those rows put another company's name inside every customer's own
 * database — and the strings were compared raw in two services, so the vendor name was load-bearing
 * in logic rather than merely cosmetic.
 */
it('exposes only neutral backing values', function (): void {
    $values = CompanyCertificateBranding::values();

    expect($values)->toBe(['platform_only', 'company_and_platform', 'company_name_only']);

    foreach ($values as $value) {
        expect(strtolower($value))->not->toContain('helbaron');
    }
});

it('defaults a new product to the neutral platform-only value', function (): void {
    $product = Product::factory()->create();

    expect($product->fresh()->company_certificate_branding)
        ->toBe(CompanyCertificateBranding::PlatformOnly);
});

it('writes the neutral value into the database column', function (): void {
    $product = Product::factory()->create([
        'company_certificate_branding' => CompanyCertificateBranding::CompanyAndPlatform->value,
    ]);

    // Read the raw column, not the cast — the cast would hide a wrong string on disk.
    $stored = DB::table('products')->where('id', $product->id)->value('company_certificate_branding');

    expect($stored)->toBe('company_and_platform');
});

it('carries the instance brand into the admin labels rather than a hardcoded vendor', function (): void {
    config([
        'branding.name.en' => 'Acme Academy',
        'branding.company_name' => 'Acme Holdings Ltd',
    ]);
    app()->forgetInstance(BrandProfilePort::class);

    $options = CompanyCertificateBranding::options();

    expect($options['platform_only'])->toContain('Acme Academy')
        ->and($options['company_and_platform'])->toContain('Acme Academy');

    foreach ($options as $label) {
        expect(strtolower($label))->not->toContain('helbaron');
    }
});

it('still labels the options when branding resolves to nothing', function (): void {
    // Every locale must be blanked: BrandProfileAdapter::localized() deliberately falls back to the
    // default locale and then to the first non-empty entry, so blanking only `en` would still
    // resolve the Arabic name — and the test would pass without exercising the empty case at all.
    config([
        'branding.name.en' => '',
        'branding.name.ar' => '',
        'branding.company_name' => '',
    ]);
    app()->forgetInstance(BrandProfilePort::class);

    // A blank brand must not produce " branding only" — a label with a leading space and no subject.
    expect(CompanyCertificateBranding::PlatformOnly->label())->toBe('the academy branding only')
        ->and(CompanyCertificateBranding::CompanyAndPlatform->label())
        ->toBe('Company logo alongside the academy');
});

/*
 * The behaviour the raw string comparisons encoded, now asked through the enum. Both directions are
 * asserted so a wrong answer in either cannot pass.
 */
it('answers the company-logo question through the enum', function (): void {
    expect(CompanyCertificateBranding::CompanyAndPlatform->includesCompanyLogo())->toBeTrue()
        ->and(CompanyCertificateBranding::PlatformOnly->includesCompanyLogo())->toBeFalse()
        ->and(CompanyCertificateBranding::CompanyNameOnly->includesCompanyLogo())->toBeFalse();
});

it('treats platform-only as the one mode that shows no company', function (): void {
    expect(CompanyCertificateBranding::PlatformOnly->showsCompany())->toBeFalse()
        ->and(CompanyCertificateBranding::CompanyAndPlatform->showsCompany())->toBeTrue()
        ->and(CompanyCertificateBranding::CompanyNameOnly->showsCompany())->toBeTrue();
});

it('does not treat a platform-only certificate as company branded', function (): void {
    $companyBranded = new Certificate(['organization_id' => 1, 'branding_mode' => 'company_and_platform']);
    $platformOnly = new Certificate(['organization_id' => 1, 'branding_mode' => 'platform_only']);
    $personal = new Certificate(['organization_id' => null, 'branding_mode' => 'company_and_platform']);

    // Certificate::isCompanyBranded() compared against the vendor literal. If the persisted rename
    // had happened without updating it, every platform-only certificate would silently have started
    // rendering as company-branded.
    expect($companyBranded->isCompanyBranded())->toBeTrue()
        ->and($platformOnly->isCompanyBranded())->toBeFalse()
        ->and($personal->isCompanyBranded())->toBeFalse();
});

it('leaves no vendor-named branding value anywhere in the database', function (): void {
    Product::factory()->count(3)->create();

    foreach (['products', 'company_entitlements'] as $table) {
        $rows = DB::table($table)
            ->whereIn('company_certificate_branding', ['helbaron_only', 'company_logo_and_helbaron'])
            ->count();

        expect($rows)->toBe(0, "{$table} still holds a vendor-named branding value");
    }
});
