<?php

namespace App\Platform\Shared\Branding\Data;

/**
 * The identity an academy is installed with: the values `install:academy` collects and writes.
 *
 * Deliberately smaller than {@see BrandProfile}. BrandProfile is the full read-side payload every
 * surface renders from — logos, colours, copyright, resolved per locale. This is only the set an
 * operator supplies once, at install, before anybody has opened the admin panel. Everything else has
 * a working default and is edited in /admin.
 */
final readonly class AcademyBrand
{
    public function __construct(
        public string $nameEn,
        public string $nameAr,
        public string $companyName,
        public string $supportEmail,
        public string $locale,
        public string $timezone,
        public string $currency,
    ) {}
}
