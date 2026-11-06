<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name_en', 'name_ar', 'slug', 'image', 'sort_order', 'is_active'])]
class Category extends Model
{
    use HasFactory, HasLocalizedAttributes, HasSlug;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class)->orderBy('sort_order')->orderBy('id');
    }

    public function activeItems(): HasMany
    {
        return $this->items()->where('is_active', true);
    }

    /** The photo Faten chose for the category, or the first item's photo when she hasn't. */
    public function coverUrl(): ?string
    {
        if ($this->image) {
            return Storage::disk('public')->url($this->image).($this->updated_at ? '?v='.$this->updated_at->timestamp : '');
        }

        return $this->relationLoaded('activeItems')
            ? $this->activeItems->map(fn (Item $item) => $item->coverUrl())->filter()->first()
            : $this->activeItems()->get()->map(fn (Item $item) => $item->coverUrl())->filter()->first();
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }
}
