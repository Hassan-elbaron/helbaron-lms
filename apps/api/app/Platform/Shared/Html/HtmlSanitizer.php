<?php

namespace App\Platform\Shared\Html;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Defense-in-depth HTML sanitizer for user-authored rich content. Wraps HTMLPurifier with a
 * strict allow-list: basic formatting, http(s)/mailto links (target=_blank with rel=noopener),
 * and http(s) images. Scripts, iframes, styles, event handlers, and javascript: URLs are
 * removed. Never sanitize with regex — this is the single sanitization point.
 */
class HtmlSanitizer
{
    private ?HTMLPurifier $purifier = null;

    public function sanitize(string $html): string
    {
        return $this->purifier()->purify($html);
    }

    /**
     * Recursively sanitize HTML-bearing string fields inside a content-blocks array.
     *
     * @param  array<array-key, mixed>  $data
     * @param  list<string>  $htmlKeys
     * @return array<array-key, mixed>
     */
    public function sanitizeArray(array $data, array $htmlKeys = ['html', 'body']): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value, $htmlKeys);
            } elseif (is_string($value) && in_array((string) $key, $htmlKeys, true)) {
                $data[$key] = $this->sanitize($value);
            }
        }

        return $data;
    }

    private function purifier(): HTMLPurifier
    {
        if ($this->purifier !== null) {
            return $this->purifier;
        }

        $config = HTMLPurifier_Config::createDefault();

        $config->set('Cache.DefinitionImpl', null); // no filesystem cache; stays side-effect free
        $config->set('HTML.Allowed', implode(',', [
            'p', 'br', 'hr', 'b', 'strong', 'i', 'em', 'u', 's', 'sub', 'sup', 'span', 'div',
            'ul', 'ol', 'li', 'blockquote', 'pre', 'code',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'a[href|title]', 'img[src|alt|title|width|height]',
            'table', 'thead', 'tbody', 'tr', 'th', 'td', 'caption',
        ]));
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('HTML.TargetBlank', true);    // external links open in a new context...
        $config->set('HTML.TargetNoopener', true); // ...without window.opener access
        $config->set('HTML.TargetNoreferrer', true);

        return $this->purifier = new HTMLPurifier($config);
    }
}
