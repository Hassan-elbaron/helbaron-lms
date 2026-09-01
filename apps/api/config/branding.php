<?php

use App\Platform\Shared\Support\Env;

/*
|--------------------------------------------------------------------------
| Instance branding defaults
|--------------------------------------------------------------------------
|
| This product ships as a SEPARATE DEPLOYED INSTANCE per customer academy, so the built-in branding
| defaults must describe *this* instance, not the vendor. These are the values BrandSetting falls
| back to before an admin saves anything in the branding screen.
|
| WHY THIS LIVES IN config/ AND NOT IN THE MODEL.
|
| `scripts/deploy.sh` runs `php artisan config:cache` on every production deploy, and Laravel's
| LoadEnvironmentVariables bootstrapper returns early when the configuration is cached — the .env
| file is never read. Any env() call made OUTSIDE the config directory therefore returns NULL in
| production. BrandSetting::defaults() used to read the BRAND_* keys directly at runtime, which meant
| the entire white-label mechanism silently stopped working the moment an instance was deployed the
| documented way: a customer who set BRAND_NAME_EN="Acme Academy" got the APP_NAME fallback instead.
|
| Config files ARE evaluated while the cache is being built, so resolving the keys here bakes the
| operator's values into the cached config and they survive. This is also exactly what larastan's
| `noEnvCallsOutsideOfConfig` rule exists to catch.
|
| Env::string (not env()) because a key written as `BRAND_NAME_AR=` is PRESENT with the value '',
| and env()'s default argument only fires when a key is ABSENT.
|
*/

$appName = Env::string('APP_NAME', 'Academy');
$nameEn = Env::string('BRAND_NAME_EN', $appName);
$nameAr = Env::string('BRAND_NAME_AR', $nameEn);
$companyName = Env::string('BRAND_COMPANY_NAME', $nameEn);
$addressEn = Env::string('BRAND_ADDRESS_EN');
$footerEn = Env::string('BRAND_EMAIL_FOOTER_EN', $companyName);

return [

    'name' => [
        'en' => $nameEn,
        'ar' => $nameAr,
    ],

    'company_name' => $companyName,

    'support_email' => Env::string('BRAND_SUPPORT_EMAIL', Env::string('MAIL_FROM_ADDRESS')),
    'support_phone' => Env::string('BRAND_SUPPORT_PHONE'),

    'address' => [
        'en' => $addressEn,
        'ar' => Env::string('BRAND_ADDRESS_AR', $addressEn),
    ],

    'timezone' => Env::string('BRAND_TIMEZONE', 'UTC'),
    'currency' => Env::string('BRAND_CURRENCY', Env::string('COMMERCE_DEFAULT_CURRENCY', 'USD')),

    'theme_preset' => Env::string('BRAND_THEME_PRESET', 'default'),

    'email' => [
        'footer' => [
            'en' => $footerEn,
            'ar' => Env::string('BRAND_EMAIL_FOOTER_AR', $footerEn),
        ],
        'signature' => [
            'en' => Env::string('BRAND_EMAIL_SIGNATURE_EN', 'The '.$nameEn.' Team'),
            'ar' => Env::string('BRAND_EMAIL_SIGNATURE_AR', 'فريق '.$nameAr),
        ],
    ],

];
