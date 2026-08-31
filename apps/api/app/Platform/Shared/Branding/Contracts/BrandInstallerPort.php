<?php

namespace App\Platform\Shared\Branding\Contracts;

use App\Platform\Shared\Branding\Data\AcademyBrand;

/**
 * The WRITE side of branding. Deliberately a separate port from {@see BrandProfilePort}.
 *
 * BrandProfilePort documents itself as "read-only by construction: it never creates the settings
 * row, so it is safe on queued mail and certificate rendering". Bolting a save() onto it would make
 * that sentence untrue for every hot-path caller that depends on it. This is the other half, used by
 * exactly one caller (`install:academy`) at exactly one moment.
 *
 * It also keeps the installer out of the Branding module's internals: the command names an academy,
 * and the Branding module decides which columns and which JSON groups that means.
 */
interface BrandInstallerPort
{
    /**
     * Write the academy's identity into the branding record.
     *
     * Idempotent and non-destructive: re-running with the same values changes nothing, and values an
     * operator has already edited by hand (support email, email footer, email signature) are never
     * overwritten. Implementations must leave the read side seeing the new values immediately.
     */
    public function install(AcademyBrand $brand): void;
}
