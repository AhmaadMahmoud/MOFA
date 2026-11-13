<?php

namespace App\Analytics;

/**
 * Where a visit came from: utm_source first (links Faten shares can carry ?utm_source=instagram),
 * else the referring site, else "direct" (typed, bookmarked, or opened from an app that hides it).
 * Instagram's in-app browser often sends no referrer: those land in "direct" unless the link has utm_source.
 */
final class Source
{
    public const ALL = ['instagram', 'facebook', 'whatsapp', 'tiktok', 'google', 'search', 'direct', 'other'];

    private const HOSTS = [
        'instagram' => ['instagram.com', 'l.instagram.com'],
        'facebook' => ['facebook.com', 'fb.com', 'l.facebook.com', 'lm.facebook.com', 'm.facebook.com', 'messenger.com'],
        'whatsapp' => ['whatsapp.com', 'wa.me', 'web.whatsapp.com'],
        'tiktok' => ['tiktok.com'],
        'google' => ['google.'],
        'search' => ['bing.com', 'duckduckgo.com', 'yahoo.', 'yandex.', 'ecosia.org'],
    ];

    /** @return array{source: string, host: ?string} */
    public static function detect(?string $referrer, mixed $utmSource, string $ownHost): array
    {
        $host = $referrer ? strtolower((string) parse_url($referrer, PHP_URL_HOST)) : '';
        $host = preg_replace('/^www\./', '', $host) ?: null;
        if ($host !== null && ($host === $ownHost || str_ends_with($host, '.'.$ownHost) || $host === preg_replace('/^www\./', '', $ownHost))) {
            $host = null; // moving around our own site
        }

        if (is_string($utmSource) && $utmSource !== '') {
            $tag = strtolower($utmSource);
            foreach (array_keys(self::HOSTS) as $source) {
                if (str_contains($tag, $source) || ($source === 'instagram' && in_array($tag, ['ig', 'insta'], true)) || ($source === 'whatsapp' && $tag === 'wa')) {
                    return ['source' => $source, 'host' => $host];
                }
            }

            return ['source' => 'other', 'host' => $host ?? substr($tag, 0, 120)];
        }

        if ($host === null) {
            return ['source' => 'direct', 'host' => null];
        }
        foreach (self::HOSTS as $source => $patterns) {
            foreach ($patterns as $pattern) {
                if ($host === $pattern || str_ends_with($host, '.'.$pattern) || (str_ends_with($pattern, '.') && str_contains($host, $pattern))) {
                    return ['source' => $source, 'host' => $host];
                }
            }
        }

        return ['source' => 'other', 'host' => substr($host, 0, 120)];
    }
}
