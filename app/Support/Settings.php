<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Shop settings Faten edits from the dashboard (Settings page).
 * Every key has a default here, so the site works before anything is saved.
 */
final class Settings
{
    public const DEFAULTS = [
        // Orders placed before this time (Cairo) can be delivered tomorrow.
        'order_cutoff' => '20:00',
        // How far ahead customers can book, in days.
        'max_days_ahead' => 7,
        // Weekdays with no delivery: 0 = Sunday … 6 = Saturday.
        'days_off' => [],
        // Orders per day, across all slots. Null = no limit.
        'daily_capacity' => null,
        'instapay_address' => null,
        'instapay_phone' => null,
        'instapay_name' => null,
        'instapay_qr' => null,
        // Customers send the InstaPay receipt here, and ask questions here.
        'contact_whatsapp' => null,
    ];

    private static ?array $cache = null;

    public static function get(string $key): mixed
    {
        $all = self::all();

        return array_key_exists($key, $all) ? $all[$key] : null;
    }

    public static function all(): array
    {
        if (self::$cache === null) {
            $saved = Schema::hasTable('settings')
                ? Setting::query()->pluck('value', 'key')->all()
                : [];
            self::$cache = array_merge(self::DEFAULTS, array_intersect_key($saved, self::DEFAULTS));
        }

        return self::$cache;
    }

    public static function put(array $values): void
    {
        foreach (array_intersect_key($values, self::DEFAULTS) as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
        self::flush();
    }

    public static function flush(): void
    {
        self::$cache = null;
    }

    public static function cutoff(): string
    {
        return (string) (self::get('order_cutoff') ?: self::DEFAULTS['order_cutoff']);
    }

    public static function maxDaysAhead(): int
    {
        return max(1, (int) self::get('max_days_ahead'));
    }

    /** @return list<int> */
    public static function daysOff(): array
    {
        return array_values(array_map('intval', (array) self::get('days_off')));
    }

    public static function dailyCapacity(): ?int
    {
        $value = self::get('daily_capacity');

        return $value === null || $value === '' ? null : (int) $value;
    }

    public static function instapayQrUrl(): ?string
    {
        $path = self::get('instapay_qr');

        return $path ? Storage::disk('public')->url($path) : null;
    }

    public static function hasInstapayDetails(): bool
    {
        return filled(self::get('instapay_address')) || filled(self::get('instapay_phone'));
    }

    /** The WhatsApp number in international form for wa.me links (201xxxxxxxxx), or null. */
    public static function whatsappNumber(): ?string
    {
        $raw = self::get('contact_whatsapp');
        if (blank($raw)) {
            return null;
        }
        $local = \App\Models\Customer::normalizePhone((string) $raw);

        return str_starts_with($local, '0') ? '2'.$local : $local;
    }

    /** A wa.me link to the shop's WhatsApp, with a message already typed. */
    public static function whatsappLink(?string $text = null): ?string
    {
        $number = self::whatsappNumber();

        return $number ? 'https://wa.me/'.$number.($text ? '?text='.rawurlencode($text) : '') : null;
    }
}
