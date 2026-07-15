<?php

namespace App\Platform\Identity\Contracts\Data;

/**
 * Immutable, read-only projection of a user for cross-context display and ownership.
 *
 * This is the ONLY user shape that may cross a context boundary. It carries identity
 * (id / publicId) plus public display fields (name / avatar / headline) and NOTHING else.
 * Secrets ($hidden: password, remember_token, two_factor_secret, two_factor_recovery_codes)
 * and account/PII internals (email, phone, is_active, verification/lock/MFA columns, locale)
 * are deliberately excluded — anything authorization-related is a *decision* exposed by a port
 * (UserPermissionPort / UserRolePort), never data on this ref.
 *
 * No Eloquent, no behavior. Produced only inside the Identity context by mapping a User model.
 */
final readonly class UserRef
{
    public function __construct(
        // Internal join key — ownership FKs (user_id / owner_id / requested_by) and owner checks.
        public int $id,
        // External identity (UUIDv7 public_id) — the only id ever exposed in APIs/URLs.
        public string $publicId,
        // Display name (also backs Filament HasName / getFilamentName).
        public string $name,
        // Public avatar path from the user's profile; null when no profile/avatar.
        public ?string $avatarPath = null,
        // Public one-line profile summary (profile.bio, rendered as "headline"); nullable.
        public ?string $headline = null,
    ) {}
}
