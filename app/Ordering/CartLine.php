<?php

namespace App\Ordering;

use App\Models\Item;

/** A cart line priced on the server. Prices from the browser are never trusted. */
final class CartLine
{
    public function __construct(
        public readonly Item $item,
        public readonly int $quantity,
    ) {}

    public function total(): int
    {
        return $this->item->price * $this->quantity;
    }
}
