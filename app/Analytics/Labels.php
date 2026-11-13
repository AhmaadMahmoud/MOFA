<?php

namespace App\Analytics;

/** Display names for the analytics codes stored in the database. */
final class Labels
{
    public static function source(?string $source): string
    {
        return match ($source) {
            'instagram' => 'Instagram',
            'facebook' => 'Facebook',
            'whatsapp' => 'WhatsApp',
            'tiktok' => 'TikTok',
            'google' => 'Google',
            'search' => __('Other search engines'),
            'direct' => __('Direct'),
            default => __('Other sites'),
        };
    }

    public static function page(?string $page): string
    {
        return match ($page) {
            'home' => __('Home page'),
            'menu' => __('Menu'),
            'item' => __('Item pages'),
            'checkout' => __('Checkout'),
            'order' => __('Order page'),
            default => (string) $page,
        };
    }

    public static function device(?string $device): string
    {
        return match ($device) {
            'mobile' => __('Mobile'),
            'tablet' => __('Tablet'),
            default => __('Computer'),
        };
    }
}
