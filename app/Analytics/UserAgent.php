<?php

namespace App\Analytics;

/** Just enough from the browser's User-Agent: is it a bot, and what kind of device. */
final class UserAgent
{
    /** Crawlers, uptime checks, and the apps that fetch a link to show a preview card. */
    private const BOTS = '/bot|crawl|spider|slurp|preview|facebookexternalhit|facebookcatalog|meta-externalagent|whatsapp|telegram|discord|skype|pinterest|embedly|curl|wget|python|go-http|java\/|okhttp|axios|node-fetch|headless|lighthouse|pagespeed|gtmetrix|uptime|monitor|scan|httpclient/i';

    public static function isBot(string $agent): bool
    {
        return $agent === '' || preg_match(self::BOTS, $agent) === 1;
    }

    public static function device(string $agent): string
    {
        return match (true) {
            (bool) preg_match('/ipad|tablet|kindle|silk|playbook|(android(?!.*mobile))/i', $agent) => 'tablet',
            (bool) preg_match('/mobi|iphone|ipod|android|blackberry|opera mini|iemobile/i', $agent) => 'mobile',
            default => 'desktop',
        };
    }

    public static function browser(string $agent): ?string
    {
        return match (true) {
            (bool) preg_match('/Instagram/i', $agent) => 'Instagram',
            (bool) preg_match('/FBAN|FBAV|FB_IAB/i', $agent) => 'Facebook',
            (bool) preg_match('/TikTok|musical_ly|Bytedance/i', $agent) => 'TikTok',
            (bool) preg_match('/SamsungBrowser/i', $agent) => 'Samsung',
            (bool) preg_match('/Edg\//i', $agent) => 'Edge',
            (bool) preg_match('/OPR\/|Opera/i', $agent) => 'Opera',
            (bool) preg_match('/Firefox|FxiOS/i', $agent) => 'Firefox',
            (bool) preg_match('/CriOS|Chrome\//i', $agent) => 'Chrome',
            (bool) preg_match('/Safari\//i', $agent) => 'Safari',
            default => null,
        };
    }

    public static function os(string $agent): ?string
    {
        return match (true) {
            (bool) preg_match('/iPhone|iPad|iPod/i', $agent) => 'iOS',
            (bool) preg_match('/Android/i', $agent) => 'Android',
            (bool) preg_match('/Windows/i', $agent) => 'Windows',
            (bool) preg_match('/Mac OS X|Macintosh/i', $agent) => 'macOS',
            (bool) preg_match('/Linux/i', $agent) => 'Linux',
            default => null,
        };
    }
}
