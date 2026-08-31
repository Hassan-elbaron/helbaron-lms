<?php

namespace App\Contexts\Commerce\Enums;

use App\Platform\Shared\Branding\Contracts\BrandProfilePort;

/**
 * Whose marks appear on a certificate earned through a company purchase. Only meaningful when the
 * product is sold to companies; individual purchases always carry platform branding.
 *
 * THE BACKING VALUES NAMED THE VENDOR. They were `helbaron_only` and `company_logo_and_helbaron`,
 * and they are COLUMN VALUES — persisted in `products.company_certificate_branding` and
 * `company_entitlements.company_certificate_branding` on every instance. A product sold as a
 * separate deployment per academy cannot ship another company's name inside its own data, and the
 * strings were also compared raw in two services, so the vendor name was load-bearing in logic.
 *
 * Renamed to `platform_only` / `company_and_platform` by
 * `2026_08_30_000300_rename_company_certificate_branding_values`, which rewrites both tables and the
 * column default. "Platform" is the neutral term for whoever operates the instance; the human label
 * resolves the actual academy name at render time through the branding port.
 */
enum CompanyCertificateBranding: string
{
    case PlatformOnly = 'platform_only';
    case CompanyAndPlatform = 'company_and_platform';
    case CompanyNameOnly = 'company_name_only';

    /**
     * The admin-facing label, carrying THIS instance's brand name.
     *
     * Resolved through BrandProfilePort rather than hardcoded, so an academy's admin reads its own
     * name in the dropdown. The port degrades to defaults and never throws, so a branding problem
     * cannot break the product form.
     */
    public function label(): string
    {
        $brand = trim(app(BrandProfilePort::class)->profile()->name);

        if ($brand === '') {
            $brand = 'the academy';
        }

        return match ($this) {
            self::PlatformOnly => $brand.' branding only',
            self::CompanyAndPlatform => 'Company logo alongside '.$brand,
            self::CompanyNameOnly => 'Company name only',
        };
    }

    /** Does this mode put the buying company's logo on the certificate? */
    public function includesCompanyLogo(): bool
    {
        return $this === self::CompanyAndPlatform;
    }

    /** Does this mode show the company at all, or only the platform's own marks? */
    public function showsCompany(): bool
    {
        return $this !== self::PlatformOnly;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
