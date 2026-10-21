<?php

namespace App\Support;

/**
 * Filament color scales built around the MOFA design system's exact colors.
 * Filament's generated palettes keep a high chroma, which turns the brand's
 * brown into orange, so the scales are written by hand in OKLCH. Each brand
 * color sits at the shade Filament uses for buttons and links (600; 500 for mauve).
 */
final class BrandPalette
{
    /** Deep Chocolate Brown #4b2c16 at 600. */
    public static function brown(): array
    {
        return [
            50 => 'oklch(0.972 0.012 54.58)',
            100 => 'oklch(0.945 0.02 54.58)',
            200 => 'oklch(0.9 0.034 54.58)',
            300 => 'oklch(0.82 0.048 54.58)',
            400 => 'oklch(0.68 0.062 54.58)',
            500 => 'oklch(0.52 0.064 54.58)',
            600 => 'oklch(0.327 0.0572 54.58)',
            700 => 'oklch(0.29 0.05 54.58)',
            800 => 'oklch(0.25 0.045 54.58)',
            900 => 'oklch(0.21 0.038 54.58)',
            950 => 'oklch(0.16 0.03 54.58)',
        ];
    }

    /** A warm, low-chroma neutral for surfaces, borders and secondary text. */
    public static function gray(): array
    {
        return [
            50 => 'oklch(0.978 0.007 62)',
            100 => 'oklch(0.948 0.018 62)',
            200 => 'oklch(0.9 0.02 62)',
            300 => 'oklch(0.83 0.024 62)',
            400 => 'oklch(0.7 0.028 62)',
            500 => 'oklch(0.52 0.034 62)',
            600 => 'oklch(0.44 0.04 62)',
            700 => 'oklch(0.38 0.042 62)',
            800 => 'oklch(0.31 0.042 62)',
            900 => 'oklch(0.25 0.038 62)',
            950 => 'oklch(0.17 0.03 62)',
        ];
    }

    /** Burnt Terracotta #b2563d at 600. */
    public static function terracotta(): array
    {
        return [
            50 => 'oklch(0.97 0.015 36.17)',
            100 => 'oklch(0.93 0.03 36.17)',
            200 => 'oklch(0.87 0.06 36.17)',
            300 => 'oklch(0.78 0.09 36.17)',
            400 => 'oklch(0.67 0.115 36.17)',
            500 => 'oklch(0.6 0.125 36.17)',
            600 => 'oklch(0.5614 0.1261 36.17)',
            700 => 'oklch(0.49 0.11 36.17)',
            800 => 'oklch(0.42 0.09 36.17)',
            900 => 'oklch(0.36 0.075 36.17)',
            950 => 'oklch(0.26 0.05 36.17)',
        ];
    }

    /** Muted Olive Green #786b49 at 600. */
    public static function olive(): array
    {
        return [
            50 => 'oklch(0.97 0.012 89.48)',
            100 => 'oklch(0.93 0.022 89.48)',
            200 => 'oklch(0.87 0.035 89.48)',
            300 => 'oklch(0.78 0.045 89.48)',
            400 => 'oklch(0.67 0.052 89.48)',
            500 => 'oklch(0.6 0.052 89.48)',
            600 => 'oklch(0.5312 0.0519 89.48)',
            700 => 'oklch(0.46 0.048 89.48)',
            800 => 'oklch(0.4 0.042 89.48)',
            900 => 'oklch(0.34 0.036 89.48)',
            950 => 'oklch(0.25 0.028 89.48)',
        ];
    }

    /** Dusty Mauve #ac7884 at 500. */
    public static function mauve(): array
    {
        return [
            50 => 'oklch(0.97 0.012 4.0)',
            100 => 'oklch(0.93 0.024 4.0)',
            200 => 'oklch(0.87 0.04 4.0)',
            300 => 'oklch(0.78 0.055 4.0)',
            400 => 'oklch(0.7 0.064 4.0)',
            500 => 'oklch(0.6288 0.0669 4.0)',
            600 => 'oklch(0.52 0.065 4.0)',
            700 => 'oklch(0.45 0.058 4.0)',
            800 => 'oklch(0.39 0.05 4.0)',
            900 => 'oklch(0.33 0.042 4.0)',
            950 => 'oklch(0.24 0.03 4.0)',
        ];
    }
}
