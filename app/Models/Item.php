<?php

namespace App\Models;

use App\Enums\ItemBadge;
use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Concerns\HasSlug;
use App\Support\Money;
use App\Support\Nutrition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'category_id', 'name_en', 'name_ar', 'slug', 'tagline_en', 'tagline_ar', 'description_en', 'description_ar',
    'price', 'badge', 'serving_size_g', 'protein_g', 'carbs_g', 'fat_g', 'fiber_g', 'sugars_g',
    'images', 'is_featured', 'is_active', 'sort_order',
])]
class Item extends Model
{
    use HasFactory, HasLocalizedAttributes, HasSlug;

    /** The nutrition facts shown on the item page, in display order. */
    public const NUTRIENTS = ['protein_g' => 'Protein', 'carbs_g' => 'Carbohydrates', 'fat_g' => 'Fat', 'fiber_g' => 'Fiber', 'sugars_g' => 'Sugars'];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'badge' => ItemBadge::class,
            'images' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'protein_g' => 'decimal:1',
            'carbs_g' => 'decimal:1',
            'fat_g' => 'decimal:1',
            'fiber_g' => 'decimal:1',
            'sugars_g' => 'decimal:1',
        ];
    }

    protected static function booted(): void
    {
        // Calories are never typed in: they follow the macros.
        static::saving(function (Item $item) {
            $item->calories = Nutrition::calories($item->protein_g, $item->carbs_g, $item->fat_g);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Ingredient lines with their amounts, in the order Faten set. */
    public function ingredientLines(): HasMany
    {
        return $this->hasMany(ItemIngredient::class)->orderBy('sort_order')->orderBy('id');
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class)->using(ItemIngredient::class)->withPivot(['quantity', 'unit', 'sort_order'])->orderByPivot('sort_order');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->whereHas('category', fn (Builder $q) => $q->where('is_active', true));
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /** Empty until a price is set, so a 0 never shows on the site. */
    public function formattedPrice(?string $locale = null): string
    {
        return $this->price > 0 ? Money::format($this->price, $locale) : '';
    }

    public function hasNutrition(): bool
    {
        return $this->calories !== null || $this->serving_size_g !== null;
    }

    /** @return list<string> public URLs of the item's photos, cover first */
    public function imageUrls(): array
    {
        return collect($this->images ?? [])
            ->filter()
            // The edit time busts the browser cache when a photo is replaced under the same name.
            ->map(fn (string $path) => Storage::disk('public')->url($path).($this->updated_at ? '?v='.$this->updated_at->timestamp : ''))
            ->values()
            ->all();
    }

    public function coverUrl(): ?string
    {
        return $this->imageUrls()[0] ?? null;
    }

    public function isOrderable(): bool
    {
        return $this->price > 0;
    }

    /** What the browser cart keeps for display. Prices are checked again on the server at checkout. */
    public function cartPayload(): array
    {
        return [
            'id' => $this->id,
            'name_en' => $this->name_en,
            'name_ar' => filled($this->name_ar) ? $this->name_ar : $this->name_en,
            'price' => $this->price,
            'image' => $this->coverUrl(),
        ];
    }
}
