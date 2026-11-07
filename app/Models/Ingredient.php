<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedAttributes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name_en', 'name_ar', 'purchase_quantity', 'purchase_unit', 'purchase_price'])]
class Ingredient extends Model
{
    use HasFactory, HasLocalizedAttributes;

    protected function casts(): array
    {
        return ['purchase_quantity' => 'decimal:2', 'purchase_price' => 'integer'];
    }

    /** "150 EGP for 300 g", or null when not priced yet. */
    public function purchaseLabel(): ?string
    {
        if (! $this->purchase_price || ! (float) $this->purchase_quantity || ! $this->purchase_unit) {
            return null;
        }

        return __(':price for :amount', [
            'price' => \App\Support\Money::format($this->purchase_price),
            'amount' => ((float) $this->purchase_quantity + 0).' '.__($this->purchase_unit),
        ]);
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class)->using(ItemIngredient::class)->withPivot(['quantity', 'unit', 'sort_order']);
    }
}
