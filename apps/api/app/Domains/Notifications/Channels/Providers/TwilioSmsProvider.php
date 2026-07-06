<?php

namespace App\Domains\Notifications\Channels\Providers;

use App\Domains\Notifications\Contracts\Providers\SmsProvider;
use Illuminate\Http\Client\Factory as HttpClient;
use RuntimeException;

/**
 * Real SMS via Twilio HTTP API. The ONLY SMS code that touches Twilio. Enabled by
 * NOTIFICATIONS_SMS_PROVIDER=twilio + services.twilio keys.
 */
class TwilioSmsProvider implements SmsProvider
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly HttpClient $http, private readonly array $config) {}

    public function send(string $to, string $body): void
    {
        $sid = (string) ($this->config['account_sid'] ?? '');
        $token = (string) ($this->config['auth_token'] ?? '');
        $from = (string) ($this->config['from'] ?? '');

        if ($sid === '' || $token === '' || $from === '') {
            throw new RuntimeException('Twilio is not configured (set TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN, TWILIO_FROM).');
        }

        $this->http
            ->baseUrl(rtrim((string) ($this->config['base_url'] ?? 'https://api.twilio.com'), '/'))
            ->withBasicAuth($sid, $token)
            ->asForm()
            ->post("/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To' => $to,
                'Body' => $body,
            ])
            ->throw();
    }
}
