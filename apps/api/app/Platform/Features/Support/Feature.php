<?php

namespace App\Platform\Features\Support;

use App\Platform\Features\Services\FeatureFlagService;
use App\Platform\Identity\Models\User;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Gate;

/**
 * Thin facade over FeatureFlagService so call sites can read Feature::enabled('events'). Resolves
 * the singleton service (its per-request flag cache is shared). Default-on semantics apply: an
 * unknown key evaluates to TRUE.
 *
 * @method static bool enabled(string $key, ?User $user = null)
 * @method static bool isEnabled(string $key, ?User $user = null)
 * @method static array<string, bool> all(?User $user = null)
 *
 * @see FeatureFlagService
 */
class Feature extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FeatureFlagService::class;
    }

    /**
     * Authorization helper mirroring the `feature` Gate (and EnsureFeatureEnabled middleware):
     * true when the feature is enabled for the user OR the user is a platform admin. Prefer this
     * over raw enabled() at call sites that must respect the admin override. Passing null resolves
     * the currently authenticated user.
     */
    public static function accessible(string $key, ?User $user = null): bool
    {
        return $user === null
            ? Gate::allows('feature', $key)
            : Gate::forUser($user)->allows('feature', $key);
    }
}
