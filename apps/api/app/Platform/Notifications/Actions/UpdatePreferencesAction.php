<?php

namespace App\Platform\Notifications\Actions;

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
    public function executeForUserId(int $userId, array $data): UserNotificationSetting
    {
        return $this->transaction(function () use ($userId, $data): UserNotificationSetting {
            $setting = UserNotificationSetting::updateOrCreate(
                ['user_id' => $userId],
                array_filter([
                    'locale' => $data['locale'] ?? null,
                    'digest_frequency' => $data['digest_frequency'] ?? null,
                    'timezone' => $data['timezone'] ?? null,
                ], fn ($v) => $v !== null),
            );

            foreach ($data['preferences'] ?? [] as $pref) {
                NotificationPreference::updateOrCreate(
                    ['user_id' => $userId, 'category' => $pref['category'], 'channel' => $pref['channel']],
                    ['enabled' => (bool) $pref['enabled']],
                );
            }

            return $setting;
        });
    }
}
