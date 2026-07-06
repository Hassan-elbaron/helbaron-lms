<?php

namespace App\Domains\Notifications\Channels\Providers;

use App\Domains\Notifications\Contracts\Providers\PushProvider;
use Illuminate\Http\Client\Factory as HttpClient;
use RuntimeException;

/**
 * Real push via Firebase Cloud Messaging. The ONLY push code that touches FCM. Enabled by
 * NOTIFICATIONS_PUSH_PROVIDER=firebase + services.firebase.server_key. (See blockers re: FCM
 * HTTP v1 — this uses the legacy HTTP endpoint for a simple, testable seam.)
 */
class FirebasePushProvider implements PushProvider
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly HttpClient $http, private readonly array $config) {}

    public function send(string $to, string $title, string $body): void
    {
        $serverKey = (string) ($this->config['server_key'] ?? '');

        if ($serverKey === '') {
            throw new RuntimeException('Firebase is not configured (set FIREBASE_SERVER_KEY).');
        }

        $this->http
            ->baseUrl(rtrim((string) ($this->config['base_url'] ?? 'https://fcm.googleapis.com'), '/'))
            ->withHeaders(['Authorization' => 'key='.$serverKey])
            ->acceptJson()
            ->post('/fcm/send', [
                'to' => $to,
                'notification' => ['title' => $title, 'body' => $body],
            ])
            ->throw();
    }
}
