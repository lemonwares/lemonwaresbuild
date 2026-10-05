<?php

namespace App\Models\Concerns;

/**
 * Text stored as {"en": {...}, "fr": {...}, "de": {...}}; empty French/German fields fall back to English.
 */
trait HasTranslatedContent
{
    public function text(string $field, ?string $locale = null): mixed
    {
        $content = is_array($this->content) ? $this->content : [];
        $locale ??= app()->getLocale();

        $value = $content[$locale][$field] ?? null;
        if ($value === null || $value === '' || $value === []) {
            $value = $content['en'][$field] ?? null;
        }

        return $value;
    }

    /**
     * @return list<string>
     */
    public function textList(string $field, ?string $locale = null): array
    {
        $value = $this->text($field, $locale);

        return is_array($value) ? array_values(array_filter($value, fn ($item) => trim((string) $item) !== '')) : [];
    }
}
