<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * The link part of a menu page (/menu/sunrise-roast), made from the English name.
 * Set once when the record is created, then kept: renaming never breaks a shared link.
 */
trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::creating(function (self $model) {
            if (blank($model->slug)) {
                $model->slug = static::uniqueSlug($model->name_en);
            }
        });
    }

    public static function uniqueSlug(?string $name): string
    {
        $base = Str::slug((string) $name) ?: Str::lower(class_basename(static::class));
        $slug = $base;

        for ($n = 2; static::query()->where('slug', $slug)->exists(); $n++) {
            $slug = "{$base}-{$n}";
        }

        return $slug;
    }
}
