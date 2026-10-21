<?php

namespace App\Support;

/**
 * Calories from macronutrients with the Atwater factors:
 * 4 kcal per gram of protein and carbohydrate, 9 per gram of fat.
 */
final class Nutrition
{
    public static function calories(int|float|string|null $protein, int|float|string|null $carbs, int|float|string|null $fat): ?int
    {
        if ($protein === null && $carbs === null && $fat === null) {
            return null;
        }

        return (int) round(4 * (float) $protein + 4 * (float) $carbs + 9 * (float) $fat);
    }
}
