<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedAttributes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name_en', 'name_ar', 'purchase_quantity', 'purchase_unit', 'purchase_price', 'stock_quantity', 'stock_unit', 'low_stock_at'])]
class Ingredient extends Model
{
    use HasFactory, HasLocalizedAttributes;

    protected function casts(): array
    {
        return [
            'purchase_quantity' => 'decimal:2',
            'purchase_price' => 'integer',
            'stock_quantity' => 'decimal:3',
            'low_stock_at' => 'decimal:3',
        ];
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

    /** Tracked ingredients at or under their warning level, or out (see App\Support\Stock::isLow). */
    public function scopeLowOnStock(Builder $query): void
    {
        $query->whereNotNull('stock_quantity')->whereNotNull('stock_unit')
            ->where(fn (Builder $q) => $q
                ->where('stock_quantity', '<=', 0)
                ->orWhere(fn (Builder $q) => $q->whereNotNull('low_stock_at')->whereColumn('stock_quantity', '<=', 'low_stock_at')));
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class)->using(ItemIngredient::class)->withPivot(['quantity', 'unit', 'sort_order']);
    }
}
