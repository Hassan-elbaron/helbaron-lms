<?php

namespace App\Domains\Notifications\Services;

use App\Domains\Identity\Models\User;
use App\Domains\Notifications\Enums\Channel;
use App\Domains\Notifications\Enums\NotificationCategory;
use App\Domains\Notifications\Models\NotificationPreference;
use App\Shared\Services\BaseService;

/**
 * Per-user, per-category, per-channel opt in/out. In-app is always on; other channels default
 * to enabled unless the user opted out.
 */
class PreferenceService extends BaseService
{
    public function isEnabled(User $user, NotificationCategory $category, Channel $channel): bool
    {
        if ($channel === Channel::InApp) {
            return true;
        }

        $pref = NotificationPreference::where('user_id', $user->id)
            ->where('category', $category->value)
            ->where('channel', $channel->value)
            ->first();

        return $pref === null ? true : $pref->enabled;
    }

    /**
     * @param  array<int, Channel>  $candidateChannels
     * @return array<int, Channel>
     */
    public function enabledChannels(User $user, NotificationCategory $category, array $candidateChannels): array
    {
        return array_values(array_filter($candidateChannels, fn (Channel $c) => $this->isEnabled($user, $category, $c)));
    }
}
