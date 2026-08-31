<?php

namespace App\Platform\Shared\Branding\Contracts;

use App\Platform\Shared\Branding\Data\BrandProfile;
use App\Platform\Shared\Branding\Data\CertificateBrand;

/**
 * The only branding surface other layers may depend on.
 *
 * Bounded contexts and platform capabilities may depend on Shared, never on the Branding module, so
 * anything that needs to render the instance's brand (mail, certificates, notification templates)
 * resolves this port instead of reaching for the BrandSetting model. Implementations must be safe on
 * hot and side-effect-sensitive paths: read-only, no row creation, and a complete profile from
 * built-in defaults when nothing has been configured.
 */
interface BrandProfilePort
{
    /**
     * The brand profile for $locale, falling back to the application's default locale and then to
     * the first configured value. Never throws and never returns null — an instance always has a
     * brand, even when the admin has saved nothing.
     */
    public function profile(?string $locale = null): BrandProfile;

    /**
     * The instance's certificate appearance.
     *
     * Same contract as profile(): read-only, memoised, and degrading to built-in defaults rather
     * than throwing — a branding failure must never cost a learner their certificate.
     *
     * Not locale-aware: images, colours, fonts and margins are the same in every language, so a
     * locale parameter here would be a lie.
     */
    public function certificate(): CertificateBrand;
}
