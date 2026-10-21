<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * One ingredient line of an item, e.g. "60 g Sourdough Bread".
 */
class ItemIngredient extends Pivot
{
    protected $table = 'ingredient_item';

    public $incrementing = true;

    protected $fillable = ['item_id', 'ingredient_id', 'quantity', 'unit', 'sort_order'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    /** "60 g", "1", "15 g": the quantity without trailing zeros, then the unit. */
    public function amountLabel(): ?string
    {
        if ($this->quantity === null) {
            return null;
        }

        // (float) "10.00" + 0 prints as "10"; "60.50" as "60.5".
        $number = (string) ((float) $this->quantity + 0);

        return trim($number.' '.__($this->unit ?? ''));
    }
}
