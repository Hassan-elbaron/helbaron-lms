<?php

namespace App\Shared\Traits;

/**
 * Convenience accessors for a JSON `seo` column holding meta fields
 * (meta_title, meta_description, og_image, canonical_url, ...). Cast `seo` to 'array'.
 * No business logic — just structured getters/setters.
 */
trait HasSeo
{
    public function seo(string $key, mixed $default = null): mixed
    {
        $seo = is_array($this->seo) ? $this->seo : [];

        return $seo[$key] ?? $default;
    }

    public function setSeo(string $key, mixed $value): static
    {
        $seo = is_array($this->seo) ? $this->seo : [];
        $seo[$key] = $value;
        $this->seo = $seo;

        return $this;
    }
}
