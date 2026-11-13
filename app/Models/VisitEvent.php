<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['visit_id', 'name', 'item_id', 'quantity', 'created_at'])]
class VisitEvent extends Model
{
    public const ADD_TO_CART = 'add_to_cart';

    public const ORDER_PLACED = 'order_placed';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'quantity' => 'integer'];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
