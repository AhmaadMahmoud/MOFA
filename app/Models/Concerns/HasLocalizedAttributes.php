<?php

namespace App\Models\Concerns;

/**
 * Bilingual columns are stored side by side as <name>_en and <name>_ar.
 * localized('name') returns the current locale's value, falling back to English.
 */
trait HasLocalizedAttributes
{
    public function localized(string $attribute, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $value = $this->getAttribute("{$attribute}_{$locale}");

        return filled($value) ? $value : $this->getAttribute("{$attribute}_en");
    }
}
