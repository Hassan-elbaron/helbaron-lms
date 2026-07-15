<?php

namespace App\Platform\Features\Services;

use App\Platform\Features\Models\FeatureFlag;
use App\Platform\Identity\Models\User;
use Illuminate\Support\Carbon;

/**
 * Central feature-flag evaluator (bound as a singleton so its per-request cache is shared).
 *
 * Evaluation order for isEnabled($key, $user):
 *   0. Env override FEATURE_{KEY}   → forces the result (emergency kill-switch / per-env toggle).
 *                                     Read directly from the process environment so it keeps
 *                                     working after `config:cache` and bypasses the DB entirely.
 *   1. Flag row MISSING            → TRUE  (never hide a feature because a flag row is absent).
 *   2. is_enabled = false          → false (the kill-switch).
 *   3. environment set and != app()->environment() → false.
 *   4. now outside [starts_at, ends_at] window      → false.
 *   5. roles set and the user has NONE of them      → false (a guest is treated as having none).
 *   6. rollout_percentage < 100    → deterministic bucket:
 *          crc32($key.'|'.($user?->id ?? 'guest')) % 100 < percentage
 *          (percentage 0 => nobody; 100/null => everyone).
 *   7. otherwise                   → TRUE.
 *
 * The flag rows are loaded ONCE per request and cached in memory.
 */
class FeatureFlagService
{
    /** @var array<string, FeatureFlag>|null Request-cached flag rows keyed by their `key`. */
    private ?array $flags = null;

    /** Evaluate a flag for the given user (null = anonymous/guest). Missing key => enabled. */
    public function isEnabled(string $key, ?User $user = null): bool
    {
        // Env override takes absolute precedence (emergency kill-switch / per-environment toggle),
        // bypassing the DB entirely so it works even when the flag row is missing.
        $override = $this->envOverride($key);
        if ($override !== null) {
            return $override;
        }

        $flag = $this->flags()[$key] ?? null;

        // Missing → default TRUE. A flag never hides a working feature just by being absent.
        if ($flag === null) {
            return true;
        }

        return $this->evaluate($flag, $user);
    }

    /** Convenience alias so the Feature facade reads as Feature::enabled('key'). */
    public function enabled(string $key, ?User $user = null): bool
    {
        return $this->isEnabled($key, $user);
    }

    /**
     * The resolved boolean map of every DEFINED flag for the given user. Only boolean keys — no
     * internals — so it is safe to hand to the frontend.
     *
     * @return array<string, bool>
     */
    public function all(?User $user = null): array
    {
        $map = [];

        foreach ($this->flags() as $key => $flag) {
            $override = $this->envOverride($key);
            $map[$key] = $override ?? $this->evaluate($flag, $user);
        }

        return $map;
    }

    /** Drop the per-request cache (e.g. after a write within the same request). */
    public function flush(): void
    {
        $this->flags = null;
    }

    private function evaluate(FeatureFlag $flag, ?User $user): bool
    {
        // Kill-switch.
        if (! $flag->is_enabled) {
            return false;
        }

        // Environment gate (null environment = all environments).
        if ($flag->environment !== null && $flag->environment !== app()->environment()) {
            return false;
        }

        // Active window.
        $now = Carbon::now();
        if ($flag->starts_at !== null && $now->lt($flag->starts_at)) {
            return false;
        }
        if ($flag->ends_at !== null && $now->gt($flag->ends_at)) {
            return false;
        }

        // Role targeting (null/[] = all roles). A guest has no roles.
        $roles = $flag->roles ?? [];
        if ($roles !== [] && ($user === null || ! $user->hasRole($roles))) {
            return false;
        }

        // Percentage rollout (null or >=100 = everyone; 0 = nobody). Deterministic per key+user.
        $percentage = $flag->rollout_percentage;
        if ($percentage !== null && $percentage < 100) {
            if ($percentage <= 0) {
                return false;
            }

            $identity = $user !== null ? (string) $user->id : 'guest';
            $bucket = crc32($flag->key.'|'.$identity) % 100;

            return $bucket < $percentage;
        }

        return true;
    }

    /**
     * Resolve a forced override for a flag from the process environment (FEATURE_{KEY_UPPER}).
     * Read straight from $_ENV/$_SERVER/getenv — NOT Laravel's cached config — so the switch keeps
     * working after `config:cache`/`artisan optimize`. Returns null when unset or unparseable
     * (fall through to the DB); a truthy value forces on, a falsy value forces off.
     */
    private function envOverride(string $key): ?bool
    {
        $name = 'FEATURE_'.strtoupper($key);
        $raw = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        if ($raw === false || $raw === '') {
            return null;
        }

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    /** @return array<string, FeatureFlag> */
    private function flags(): array
    {
        if ($this->flags === null) {
            $this->flags = [];

            foreach (FeatureFlag::query()->get() as $flag) {
                $this->flags[$flag->key] = $flag;
            }
        }

        return $this->flags;
    }
}
