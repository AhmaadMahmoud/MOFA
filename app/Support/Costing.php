<?php

namespace App\Support;

use App\Models\Ingredient;
use App\Models\Item;

/**
 * What an item costs to make, and what it earns.
 *
 * Each ingredient has a purchase (e.g. 300 g of beef for 150 EGP); each item line says how
 * much goes in (e.g. 60 g). Units are brought to a base (g, ml, piece) so an ingredient
 * bought by the kilo can be used by the gram. All money is in piasters.
 */
final class Costing
{
    /** Units an ingredient can be bought in => [base unit, how many base units]. */
    public const PURCHASE_UNITS = [
        'g' => ['g', 1],
        'kg' => ['g', 1000],
        'ml' => ['ml', 1],
        'l' => ['ml', 1000],
        'pc' => ['pc', 1],
    ];

    /** Cost of one base unit (one gram, millilitre or piece), in piasters; null when not priced. */
    public static function unitCost(?Ingredient $ingredient): ?float
    {
        [$base, $size] = self::purchaseBase($ingredient) ?? [null, null];

        return $base === null ? null : $ingredient->purchase_price / $size;
    }

    /** [base unit, quantity in base units] of an ingredient's purchase; null when not priced. */
    public static function purchaseBase(?Ingredient $ingredient): ?array
    {
        if (! $ingredient || ! $ingredient->purchase_price || ! (float) $ingredient->purchase_quantity) {
            return null;
        }

        [$base, $factor] = self::PURCHASE_UNITS[$ingredient->purchase_unit] ?? [null, null];

        return $base === null ? null : [$base, (float) $ingredient->purchase_quantity * $factor];
    }

    /**
     * Cost of one line ("60 g of beef"), in piasters.
     * Null with a reason when it can't be worked out.
     *
     * @return array{cost: ?float, problem: ?string}
     */
    public static function line(?Ingredient $ingredient, int|float|string|null $quantity, ?string $unit): array
    {
        if (! $ingredient) {
            return ['cost' => null, 'problem' => null];
        }
        if (! self::purchaseBase($ingredient)) {
            return ['cost' => null, 'problem' => 'unpriced'];
        }
        if ($quantity === null || $quantity === '') {
            return ['cost' => null, 'problem' => 'no-amount'];
        }
        // A bare count ("1" egg) is in pieces.
        $unit = $unit ?: 'pc';

        [$base, $factor] = self::PURCHASE_UNITS[$unit] ?? [null, null];
        [$boughtIn] = self::purchaseBase($ingredient);

        if ($base !== $boughtIn) {
            return ['cost' => null, 'problem' => 'unit-mismatch'];
        }

        return ['cost' => (float) $quantity * $factor * self::unitCost($ingredient), 'problem' => null];
    }

    /**
     * The full picture for one item.
     *
     * @param  iterable<array{ingredient: ?Ingredient, quantity: mixed, unit: ?string}>  $lines
     * @return array{lines: list<array{name: string, cost: ?float, problem: ?string}>, ingredients: float, packaging: int, total: float, price: ?int, profit: ?float, margin: ?float, complete: bool}
     */
    public static function summary(iterable $lines, ?int $packaging, ?int $price): array
    {
        $out = [];
        $ingredients = 0.0;
        $complete = true;

        foreach ($lines as $line) {
            if (! $line['ingredient']) {
                continue;
            }
            $result = self::line($line['ingredient'], $line['quantity'], $line['unit']);
            $ingredients += $result['cost'] ?? 0;
            $complete = $complete && $result['problem'] === null;
            $out[] = ['name' => $line['ingredient']->localized('name'), ...$result];
        }

        $total = $ingredients + ($packaging ?? 0);
        $profit = $price ? $price - $total : null;

        return [
            'lines' => $out,
            'ingredients' => $ingredients,
            'packaging' => $packaging ?? 0,
            'total' => $total,
            'price' => $price,
            'profit' => $profit,
            'margin' => $price ? $profit / $price * 100 : null,
            'complete' => $complete && $out !== [],
        ];
    }

    /** Summary for a saved item (expects ingredientLines.ingredient loaded or loads them). */
    public static function forItem(Item $item): array
    {
        $item->loadMissing('ingredientLines.ingredient');

        return self::summary(
            $item->ingredientLines->map(fn ($l) => ['ingredient' => $l->ingredient, 'quantity' => $l->quantity, 'unit' => $l->unit]),
            $item->packaging_cost,
            $item->price,
        );
    }

    /** Money with piasters kept when there are any: 12.35 EGP. */
    public static function money(float|int|null $piasters): string
    {
        return $piasters === null ? '—' : Money::format((int) round($piasters));
    }

    /** Filament colour for a margin: under 30% is low, under 50% is fair. */
    public static function marginColor(?float $margin): string
    {
        return match (true) {
            $margin === null => 'gray',
            $margin < 30 => 'danger',
            $margin < 50 => 'warning',
            default => 'success',
        };
    }
}
