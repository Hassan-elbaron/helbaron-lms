<?php

namespace App\Platform\Notifications\Actions;

use App\Platform\Identity\Models\User;
use App\Platform\Notifications\Models\NotificationPreference;
use App\Platform\Notifications\Models\UserNotificationSetting;
use App\Platform\Shared\Actions\BaseAction;

/**
 * Updates a user's notification settings (locale, digest) and per-category/channel preferences.
 */
class UpdatePreferencesAction extends BaseAction
{
    /**
     * @param  array{locale?: string, digest_frequency?: string, timezone?: string, preferences?: array<int, array{category: string, channel: string, enabled: bool}>}  $data
     */
    public function execute(User $user, array $data): UserNotificationSetting
    {
        return $this->transaction(function () use ($user, $data): UserNotificationSetting {
            $setting = UserNotificationSetting::updateOrCreate(
                ['user_id' => $user->id],
                array_filter([
                    'locale' => $data['locale'] ?? null,
                    'digest_frequency' => $data['digest_frequency'] ?? null,
                    'timezone' => $data['timezone'] ?? null,
                ], fn ($v) => $v !== null),
            );

            foreach ($data['preferences'] ?? [] as $pref) {
                NotificationPreference::updateOrCreate(
                    ['user_id' => $user->id, 'category' => $pref['category'], 'channel' => $pref['channel']],
                    ['enabled' => (bool) $pref['enabled']],
                );
            }

            return $setting;
        });
    }
}
