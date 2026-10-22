<?php

namespace App\Ordering;

use App\Models\Item;
use Illuminate\Support\Collection;

/**
 * Turns the browser's cart ({item id: quantity}) into priced lines.
 * Items that are hidden, unpriced or gone are dropped.
 */
final class Cart
{
    public const MAX_QUANTITY = 20;

    public const MAX_LINES = 30;

    /** @var Collection<int, CartLine> */
    public readonly Collection $lines;

    /** @param  array<int|string, int|string>  $quantities */
    public function __construct(array $quantities)
    {
        $wanted = [];
        foreach ($quantities as $id => $qty) {
            $id = (int) $id;
            $qty = min(self::MAX_QUANTITY, (int) $qty);
            if ($id > 0 && $qty > 0) {
                $wanted[$id] = $qty;
            }
        }
        $wanted = array_slice($wanted, 0, self::MAX_LINES, true);

        $items = $wanted === []
            ? collect()
            : Item::query()->active()->where('price', '>', 0)->whereKey(array_keys($wanted))->get()->keyBy('id');

        $this->lines = collect($wanted)
            ->filter(fn ($qty, $id) => $items->has($id))
            ->map(fn ($qty, $id) => new CartLine($items[$id], $qty))
            ->values();
    }

    public function isEmpty(): bool
    {
        return $this->lines->isEmpty();
    }

    public function subtotal(): int
    {
        return $this->lines->sum(fn (CartLine $l) => $l->total());
    }

    public function count(): int
    {
        return $this->lines->sum('quantity');
    }

    /** @return array<int, int> */
    public function quantities(): array
    {
        return $this->lines->mapWithKeys(fn (CartLine $l) => [$l->item->id => $l->quantity])->all();
    }
}
