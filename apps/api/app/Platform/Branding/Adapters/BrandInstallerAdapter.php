<?php

namespace App\Platform\Branding\Adapters;

use App\Platform\Branding\Models\BrandSetting;
use App\Platform\Shared\Branding\Contracts\BrandInstallerPort;
use App\Platform\Shared\Branding\Contracts\BrandProfilePort;
use App\Platform\Shared\Branding\Data\AcademyBrand;

/**
 * Writes the academy identity into the BrandSetting singleton.
 *
 * The counterpart to {@see BrandProfileAdapter}, kept separate so the read adapter's promise — never
 * writes, never creates the row, safe on queued mail — stays literally true.
 *
 * TWO RULES GOVERN EVERYTHING HERE.
 *
 * 1. Never overwrite what an operator wrote. `install:academy --force` is a routine way to pick up
 *    new structural seed data after a release; it must not silently revert a support address or an
 *    email footer somebody edited in /admin months ago.
 *
 * 2. The read side must see the new values immediately. BrandProfileAdapter memoises per process and
 *    the structural seeders resolve the academy name AS THEY WRITE, in the same process, moments
 *    after this runs. A stale memo here means static pages, navigation and SEO records seeded under
 *    the previous name — permanently, because those seeders are firstOrCreate.
 */
class BrandInstallerAdapter implements BrandInstallerPort
{
    public function install(AcademyBrand $brand): void
    {
        $setting = BrandSetting::current();

        $setting->update([
            'identity' => $this->identity($setting, $brand),
            'email' => $this->email($setting, $brand),
        ]);

        // Rule 2. Dropping the singleton is enough: the next resolution rebuilds from the row.
        app()->forgetInstance(BrandProfilePort::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function identity(BrandSetting $setting, AcademyBrand $brand): array
    {
        $identity = is_array($setting->identity) ? $setting->identity : [];

        $identity['brand_name'] = ['en' => $brand->nameEn, 'ar' => $brand->nameAr];
        $identity['short_name'] = $brand->nameEn;
        $identity['company_name'] = $brand->companyName;
        $identity['default_language'] = $brand->locale;
        $identity['timezone'] = $brand->timezone;
        $identity['currency'] = $brand->currency;

        // Rule 1: an install that supplies no support address must not blank an existing one.
        if ($brand->supportEmail !== '') {
            $identity['support_email'] = $brand->supportEmail;
        }

        return $identity;
    }

    /**
     * The email footer and signature, so EMAILS resolve the academy name from the database.
     *
     * Left unset these fall through to config('branding.email.*'), which resolves BRAND_EMAIL_* ->
     * BRAND_COMPANY_NAME -> BRAND_NAME_EN -> APP_NAME. An academy installed with an explicit name but
     * without those environment variables would show one name on the site and a different one at the
     * bottom of every email it sends — two sources of truth for one fact.
     *
     * The sentence shapes mirror config/branding.php's fallbacks on purpose, so an instance that
     * configures neither reads identically either way.
     *
     * @return array<string, mixed>
     */
    private function email(BrandSetting $setting, AcademyBrand $brand): array
    {
        $email = is_array($setting->email) ? $setting->email : [];

        $defaults = [
            'footer' => ['en' => $brand->companyName, 'ar' => $brand->companyName],
            'signature' => ['en' => 'The '.$brand->nameEn.' Team', 'ar' => 'فريق '.$brand->nameAr],
        ];

        foreach ($defaults as $group => $values) {
            foreach ($values as $locale => $value) {
                // Rule 1, per value: only fill what is genuinely empty.
                if (trim((string) ($email[$group][$locale] ?? '')) === '') {
                    $email[$group][$locale] = $value;
                }
            }
        }

        return $email;
    }
}
