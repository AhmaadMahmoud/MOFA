<?php

namespace App\Support;

/**
 * Prices are stored as integer piasters (EGP × 100) and shown in EGP.
 */
final class Money
{
    public static function toPiasters(int|float|string|null $pounds): ?int
    {
        return $pounds === null || $pounds === '' ? null : (int) round((float) $pounds * 100);
    }

    public static function toPounds(?int $piasters): ?float
    {
        return $piasters === null ? null : $piasters / 100;
    }

    /** "95 EGP" / "95 ج.م", with decimals only when there are piasters. */
    public static function format(?int $piasters, ?string $locale = null): string
    {
        if ($piasters === null) {
            return '';
        }

        $amount = $piasters % 100 === 0
            ? number_format(intdiv($piasters, 100))
            : number_format($piasters / 100, 2);

        return ($locale ?? app()->getLocale()) === 'ar' ? $amount.' ج.م' : $amount.' EGP';
    }
}
