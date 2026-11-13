<?php

namespace App\Support;

use App\Models\Ingredient;
use App\Models\Order;
use App\Notifications\AdminAlerts;
use Illuminate\Support\Facades\DB;

/**
 * Ingredient stock.
 *
 * When Faten confirms an order's payment, the ingredients its items use come out of stock
 * (each item's ingredient lines × how many were ordered). Cancelling a confirmed order puts
 * back exactly what was taken, as recorded on the order. Only ingredients with a stock set
 * are tracked; the others are left alone. Stock may go below zero: the sandwiches were
 * still made, and Faten should see that the numbers need a recount.
 *
 * Units: a line in "g" can come out of stock kept in "kg" (both are weights); a bare count
 * is in pieces. A line whose unit can't be turned into the stock unit (grams of something
 * counted in pieces) is skipped.
 */
final class Stock
{
    /** Is this ingredient's stock tracked? */
    public static function tracked(?Ingredient $ingredient): bool
    {
        return $ingredient !== null
            && $ingredient->stock_quantity !== null
            && isset(Costing::PURCHASE_UNITS[$ingredient->stock_unit]);
    }

    /** $quantity of $from, expressed in $to; null when one can't become the other (weight vs pieces). */
    public static function convert(float $quantity, ?string $from, string $to): ?float
    {
        [$fromBase, $fromFactor] = Costing::PURCHASE_UNITS[$from ?: 'pc'] ?? [null, null];
        [$toBase, $toFactor] = Costing::PURCHASE_UNITS[$to] ?? [null, null];

        if ($fromBase === null || $fromBase !== $toBase) {
            return null;
        }

        return $quantity * $fromFactor / $toFactor;
    }

    /** At or under its warning level (or out). */
    public static function isLow(Ingredient $ingredient): bool
    {
        if (! self::tracked($ingredient)) {
            return false;
        }
        $left = (float) $ingredient->stock_quantity;

        return $left <= 0 || ($ingredient->low_stock_at !== null && $left <= (float) $ingredient->low_stock_at);
    }

    /** "1.25 kg", without trailing zeros. */
    public static function label(int|float|string|null $quantity, ?string $unit): string
    {
        return trim(((float) $quantity + 0).' '.__($unit ?? ''));
    }

    /**
     * Take the order's ingredients out of stock. Runs once per order: a second call does nothing.
     * Call it inside the transaction that confirms the order; the alerts go out after it commits.
     */
    public static function takeFor(Order $order): void
    {
        if ($order->stock_taken !== null) {
            return;
        }

        $order->loadMissing('items.item.ingredientLines.ingredient');

        // Add up per ingredient first, so two items sharing an ingredient are one update.
        $needs = [];
        foreach ($order->items as $orderItem) {
            foreach ($orderItem->item?->ingredientLines ?? [] as $line) {
                $ingredient = $line->ingredient;
                if (! self::tracked($ingredient) || $line->quantity === null) {
                    continue;
                }
                $amount = self::convert((float) $line->quantity * $orderItem->quantity, $line->unit, $ingredient->stock_unit);
                if ($amount === null) {
                    continue;
                }
                $needs[$ingredient->id] = ($needs[$ingredient->id] ?? 0) + $amount;
            }
        }

        $taken = [];
        $nowLow = [];
        foreach ($needs as $id => $amount) {
            $ingredient = Ingredient::query()->lockForUpdate()->find($id);
            if (! self::tracked($ingredient)) {
                continue;
            }
            $wasLow = self::isLow($ingredient);
            $ingredient->stock_quantity = round((float) $ingredient->stock_quantity - $amount, 3);
            $ingredient->save();

            $taken[] = ['ingredient_id' => $id, 'quantity' => round($amount, 4), 'unit' => $ingredient->stock_unit];
            if (! $wasLow && self::isLow($ingredient)) {
                $nowLow[] = $ingredient;
            }
        }

        $order->forceFill(['stock_taken' => $taken])->save();

        // Only when an ingredient just crossed the line, so the bell doesn't ring on every order.
        DB::afterCommit(function () use ($nowLow) {
            foreach ($nowLow as $ingredient) {
                AdminAlerts::lowStock($ingredient);
            }
        });
    }

    /** Put back what the order took (when a confirmed order is cancelled). */
    public static function returnFor(Order $order): void
    {
        foreach ($order->stock_taken ?? [] as $entry) {
            $ingredient = Ingredient::query()->lockForUpdate()->find($entry['ingredient_id'] ?? null);
            if (! self::tracked($ingredient)) {
                continue;
            }
            // Faten may have changed the stock unit since (kg → g).
            $amount = self::convert((float) $entry['quantity'], $entry['unit'] ?? null, $ingredient->stock_unit);
            if ($amount === null) {
                continue;
            }
            $ingredient->stock_quantity = round((float) $ingredient->stock_quantity + $amount, 3);
            $ingredient->save();
        }

        $order->forceFill(['stock_taken' => null])->save();
    }
}
