<?php

namespace App\Platform\Identity\Contracts;

use App\Platform\Identity\Contracts\Data\UserRef;

/**
 * Reads the already-authenticated principal for business code, so callers stop reaching for
 * request()->user() / an injected User model just to learn "who am I acting as".
 *
 * This port does NOT authenticate — no login, tokens, password/OTP/MFA, or session handling.
 * It only reflects the principal the framework auth guard has already resolved. Implemented
 * inside the Identity context (later phase).
 */
interface CurrentUserPort
{
    /** The authenticated user's internal id, or null when unauthenticated (guest). */
    public function currentUserId(): ?int;

    /** The authenticated user as a boundary-safe UserRef, or null when unauthenticated. */
    public function currentUserRef(): ?UserRef;

    /** True when a user is authenticated on the current request. */
    public function isAuthenticated(): bool;
}
