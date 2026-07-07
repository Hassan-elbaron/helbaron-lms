<?php

namespace App\Domains\Notifications\Services;

use App\Domains\Notifications\Data\RenderedMessage;
use App\Domains\Notifications\Enums\Channel;
use App\Domains\Notifications\Models\NotificationTemplate;
use App\Platform\Shared\Services\BaseService;

/**
 * Renders a template for a channel + locale, falling back to the app fallback locale, then to a
 * generic message. Substitutes {{ var }} placeholders from data.
 */
class TemplateRenderer extends BaseService
{
    /** @param array<string, mixed> $data */
    public function render(string $key, Channel $channel, string $locale, array $data): RenderedMessage
    {
        $template = $this->find($key, $channel, $locale);

        if ($template === null) {
            // Generic fallback keeps delivery resilient even without a template.
            $subject = (string) ($data['title'] ?? ucfirst(str_replace('_', ' ', $key)));

            return new RenderedMessage($subject, (string) ($data['body'] ?? $subject), $locale);
        }

        return new RenderedMessage(
            subject: $this->substitute((string) $template->subject, $data),
            body: $this->substitute((string) $template->body, $data),
            locale: $template->locale,
        );
    }

    private function find(string $key, Channel $channel, string $locale): ?NotificationTemplate
    {
        $fallback = (string) config('notifications.locale.fallback', 'en');

        return NotificationTemplate::where('key', $key)->where('channel', $channel->value)->where('is_active', true)
            ->where('locale', $locale)->first()
            ?? NotificationTemplate::where('key', $key)->where('channel', $channel->value)->where('is_active', true)
                ->where('locale', $fallback)->first();
    }

    /** @param array<string, mixed> $data */
    private function substitute(string $text, array $data): string
    {
        foreach ($data as $k => $v) {
            if (is_scalar($v)) {
                $text = str_replace('{{ '.$k.' }}', (string) $v, $text);
            }
        }

        return $text;
    }
}
