<?php

namespace App\Platform\Notifications\Channels\Providers;

use App\Platform\Notifications\Contracts\Providers\MailProvider;
use Illuminate\Http\Client\Factory as HttpClient;
use RuntimeException;

/**
 * Real email via Mailgun HTTP API. The ONLY email code that touches Mailgun. Enabled by
 * NOTIFICATIONS_MAIL_PROVIDER=mailgun + services.mailgun keys.
 */
class MailgunMailProvider implements MailProvider
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly HttpClient $http, private readonly array $config) {}

    public function send(string $to, string $subject, string $body): void
    {
        $domain = (string) ($this->config['domain'] ?? '');
        $secret = (string) ($this->config['secret'] ?? '');

        if ($domain === '' || $secret === '') {
            throw new RuntimeException('Mailgun is not configured (set MAILGUN_DOMAIN and MAILGUN_SECRET).');
        }

        $this->http
            ->baseUrl(rtrim((string) ($this->config['base_url'] ?? 'https://api.mailgun.net'), '/'))
            ->withBasicAuth('api', $secret)
            ->asForm()
            ->post("/v3/{$domain}/messages", [
                'from' => (string) ($this->config['from'] ?? 'no-reply@helbaron.test'),
                'to' => $to,
                'subject' => $subject,
                'html' => $body,
            ])
            ->throw();
    }
}
