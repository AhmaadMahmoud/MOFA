<?php

namespace App\Support;

/**
 * Short notes Faten writes under an item's photo ("Suitable for diabetics", "Spicy"),
 * each with its own font, text colour and background, from the brand palette only,
 * so whatever she picks still looks like MOFA.
 */
final class ItemNote
{
    public const COLORS = [
        'brown' => ['#4b2c16', 'Deep brown'],
        'terracotta' => ['#b2563d', 'Terracotta'],
        'olive' => ['#786b49', 'Olive'],
        'mauve' => ['#ac7884', 'Mauve'],
        'cream' => ['#e0c3a4', 'Cream'],
        'paper' => ['#f6ece1', 'Paper'],
    ];

    public const FONTS = [
        'script' => 'Handwritten',
        'retro' => 'Bold retro',
        'serif' => 'Classic',
        'sans' => 'Simple',
    ];

    public const SHAPES = [
        'brush' => 'Brush stroke',
        'pill' => 'Pill',
        'none' => 'Text only',
    ];

    /** @return array<string, string> key => label with a colour swatch, for the dashboard selects */
    public static function colorOptions(): array
    {
        return collect(self::COLORS)->mapWithKeys(fn ($c, $key) => [
            $key => '<span style="display:inline-flex;align-items:center;gap:.5rem"><span style="width:1rem;height:1rem;border-radius:999px;background:'.$c[0].';box-shadow:inset 0 0 0 1px rgb(0 0 0/.12)"></span>'.e(__($c[1])).'</span>',
        ])->all();
    }

    /** @return array<string, string> */
    public static function fontOptions(): array
    {
        return collect(self::FONTS)->map(fn ($label) => __($label))->all();
    }

    /** @return array<string, string> */
    public static function shapeOptions(): array
    {
        return collect(self::SHAPES)->map(fn ($label) => __($label))->all();
    }

    /** A sensible starting look: brown handwriting on a mauve brush, like the item tagline. */
    public const DEFAULTS = ['font' => 'script', 'color' => 'brown', 'background' => 'mauve', 'shape' => 'brush'];

    /**
     * Inline style variables and the class for one note (see .mofa-note in both stylesheets).
     *
     * @param  array<string, mixed>  $note
     * @return array{class: string, style: string}
     */
    public static function look(array $note): array
    {
        $note += self::DEFAULTS;
        $color = self::COLORS[$note['color']][0] ?? self::COLORS['brown'][0];
        $background = self::COLORS[$note['background']][0] ?? self::COLORS['mauve'][0];
        $font = array_key_exists($note['font'], self::FONTS) ? $note['font'] : 'script';
        $shape = array_key_exists($note['shape'], self::SHAPES) ? $note['shape'] : 'brush';

        return [
            'class' => "mofa-note mofa-note--{$shape} mofa-note--{$font}",
            'style' => "--note-color: {$color}; --note-bg: {$background};",
        ];
    }

    /** The note's text in the given language, falling back to the other one. */
    public static function text(array $note, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $text = $note['text_'.$locale] ?? null;

        return filled($text) ? $text : (filled($note['text_en'] ?? null) ? $note['text_en'] : ($note['text_ar'] ?? null));
    }
}
