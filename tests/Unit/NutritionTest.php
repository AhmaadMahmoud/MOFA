<?php

namespace Tests\Unit;

use App\Support\Money;
use App\Support\Nutrition;
use PHPUnit\Framework\TestCase;

class NutritionTest extends TestCase
{
    public function test_calories_follow_the_atwater_factors(): void
    {
        // The Sunrise Roast post: 40 g protein, 38 g carbs, 27 g fat.
        $this->assertSame(555, Nutrition::calories(40, 38, 27));
        $this->assertSame(555, Nutrition::calories('40.0', '38.0', '27.0'));
    }

    public function test_missing_macros_count_as_zero_but_none_means_unknown(): void
    {
        $this->assertSame(36, Nutrition::calories(null, null, 4));
        $this->assertNull(Nutrition::calories(null, null, null));
    }

    public function test_money_is_stored_in_piasters_and_shown_in_pounds(): void
    {
        $this->assertSame(9550, Money::toPiasters('95.5'));
        $this->assertSame(9500, Money::toPiasters(95));
        $this->assertNull(Money::toPiasters(''));
        $this->assertSame('95 EGP', Money::format(9500, 'en'));
        $this->assertSame('95.50 EGP', Money::format(9550, 'en'));
        $this->assertSame('1,250 ج.م', Money::format(125000, 'ar'));
    }
}
