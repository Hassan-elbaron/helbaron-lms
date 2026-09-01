<?php

namespace App\Platform\Notifications\Services;

use App\Platform\Notifications\Data\RenderedMessage;
use App\Platform\Notifications\Enums\Channel;
use App\Platform\Notifications\Models\NotificationTemplate;
use App\Platform\Shared\Branding\Contracts\BrandProfilePort;
use App\Platform\Shared\Services\BaseService;

/**
 * Renders a template for a channel + locale, falling back to the app fallback locale, then to a
 * generic message. Substitutes {{ var }} placeholders from data.
 *
 * Brand variables ({{ brand }}, {{ brand_company }}, {{ brand_support_email }}, {{ brand_signature }},
 * ...) are injected automatically for the render locale, so template authors can write white-label
 * copy instead of baking one academy's name into the row. Caller-supplied data always wins, so a
 * caller may still override any of them explicitly.
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

        $variables = $this->withBrandVariables($data, $template->locale);

        return new RenderedMessage(
            subject: $this->substitute((string) $template->subject, $variables),
            body: $this->substitute((string) $template->body, $variables),
            locale: $template->locale,
        );
    }

    /**
     * Merge the instance brand variables under the caller's data (caller wins on key collisions).
     *
     * Branding must never be able to break a delivery, so a failure to resolve it degrades to the
     * caller's data unchanged rather than throwing out of a queued notification.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withBrandVariables(array $data, string $locale): array
    {
        try {
            $brand = app(BrandProfilePort::class)->profile($locale)->templateVariables();
        } catch (\Throwable) {
            return $data;
        }

        return array_merge($brand, $data);
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
