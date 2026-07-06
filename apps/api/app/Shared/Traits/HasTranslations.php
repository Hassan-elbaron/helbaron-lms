<?php

namespace App\Shared\Traits;

use App\Shared\Helpers\LocaleHelper;

/**
 * Lightweight JSON translations for model attributes. Translatable attributes are stored as
 * JSON maps of locale => value. Declare which attributes are translatable via
 * `protected array $translatable = ['title', 'description'];` and cast them to 'array'.
 *
 * This is a minimal shared helper — not a full i18n engine and no business logic.
 */
trait HasTranslations
{
    /** Get a translated value, falling back to the app fallback locale. */
    public function translate(string $attribute, ?string $locale = null): mixed
    {
        $locale ??= LocaleHelper::current();
        $value = $this->{$attribute};

        if (! is_array($value)) {
            return $value;
        }

        return $value[$locale]
            ?? $value[LocaleHelper::fallback()]
            ?? (reset($value) ?: null);
    }

    /** Set a translation for a single locale without dropping the others. */
    public function setTranslation(string $attribute, string $locale, mixed $value): static
    {
        $current = is_array($this->{$attribute}) ? $this->{$attribute} : [];
        $current[$locale] = $value;
        $this->{$attribute} = $current;

        return $this;
    }

    /** @return array<int, string> */
    public function translatableAttributes(): array
    {
        return property_exists($this, 'translatable') ? (array) $this->translatable : [];
    }
}
