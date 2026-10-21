<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * The site's locales (config/app.php: supported_locales, rtl_locales).
 */
final class Locales
{
    public const COOKIE = 'mofa_locale';

    /** @return list<string> */
    public static function supported(): array
    {
        return config('app.supported_locales', ['ar', 'en']);
    }

    public static function default(): string
    {
        return self::supported()[0];
    }

    public static function isSupported(?string $locale): bool
    {
        return $locale !== null && in_array($locale, self::supported(), true);
    }

    public static function isRtl(?string $locale = null): bool
    {
        return in_array($locale ?? app()->getLocale(), config('app.rtl_locales', ['ar']), true);
    }

    public static function dir(?string $locale = null): string
    {
        return self::isRtl($locale) ? 'rtl' : 'ltr';
    }

    /** The other locale, for the one-tap language switch. */
    public static function other(?string $locale = null): string
    {
        $current = $locale ?? app()->getLocale();

        return collect(self::supported())->first(fn (string $l) => $l !== $current) ?? self::default();
    }

    /** The current page in another locale, keeping its route parameters (e.g. the item). */
    public static function urlFor(string $locale): string
    {
        $route = request()->route();

        if (! $route?->getName()) {
            return route('home', ['locale' => $locale]);
        }

        return route($route->getName(), array_merge($route->parameters(), ['locale' => $locale]));
    }

    /** The visitor's locale: their saved choice, else their browser's language, else the default. */
    public static function preferred(Request $request): string
    {
        $saved = $request->cookie(self::COOKIE);

        if (self::isSupported($saved)) {
            return $saved;
        }

        return $request->getPreferredLanguage(self::supported()) ?? self::default();
    }
}
