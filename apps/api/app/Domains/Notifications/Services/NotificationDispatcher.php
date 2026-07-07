<?php

namespace App\Domains\Notifications\Services;

use App\Platform\Identity\Models\User;
use App\Domains\Notifications\Enums\Channel;
use App\Domains\Notifications\Enums\DeliveryStatus;
use App\Domains\Notifications\Enums\NotificationCategory;
use App\Domains\Notifications\Jobs\DeliverNotificationJob;
use App\Domains\Notifications\Models\Notification;
use App\Domains\Notifications\Models\NotificationDelivery;
use App\Domains\Notifications\Models\UserNotificationSetting;
use App\Platform\Shared\Services\BaseService;

/**
 * Creates a notification + per-channel delivery rows (idempotent by dedup key) and queues each
 * delivery. Never sends inline — all delivery is queued.
 */
class NotificationDispatcher extends BaseService
{
    public function __construct(
        private readonly TemplateRenderer $renderer,
        private readonly PreferenceService $preferences,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, Channel>|null  $channels
     */
    public function dispatch(User $user, NotificationCategory $category, string $templateKey, array $data = [], ?array $channels = null, ?string $dedupKey = null): Notification
    {
        $locale = $this->localeFor($user);

        $inApp = $this->renderer->render($templateKey, Channel::InApp, $locale, $data);

        $candidate = $channels ?? $this->defaultChannels();
        $enabled = $this->preferences->enabledChannels($user, $category, $candidate);

        return $this->transaction(function () use ($user, $category, $templateKey, $data, $locale, $inApp, $enabled, $dedupKey): Notification {
            $notification = Notification::create([
                'user_id' => $user->id,
                'category' => $category->value,
                'type' => $templateKey,
                'title' => $inApp->subject,
                'body' => $inApp->body,
                'data' => $data,
                'locale' => $locale,
            ]);

            foreach ($enabled as $channel) {
                $delivery = NotificationDelivery::firstOrCreate(
                    ['notification_id' => $notification->id, 'channel' => $channel->value],
                    [
                        'status' => DeliveryStatus::Pending->value,
                        'dedup_key' => $dedupKey ?? ($templateKey.':'.$user->id.':'.$channel->value.':'.now()->format('YmdHi')),
                    ],
                );

                // Queue the delivery (never sent inline).
                DeliverNotificationJob::dispatch($delivery->id)->onQueue((string) config('notifications.queue', 'notifications'));
            }

            return $notification;
        });
    }

    /** @return array<int, Channel> */
    private function defaultChannels(): array
    {
        return array_map(fn (string $c) => Channel::from($c), (array) config('notifications.default_channels', ['in_app']));
    }

    private function localeFor(User $user): string
    {
        $setting = UserNotificationSetting::where('user_id', $user->id)->first();

        return $setting?->locale ?? (string) config('notifications.locale.default', 'en');
    }
}
