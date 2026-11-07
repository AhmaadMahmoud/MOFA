<?php

namespace Tests\Feature;

use App\Filament\Pages\Costs;
use App\Models\Ingredient;
use App\Models\Item;
use App\Models\User;
use App\Support\Costing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostingTest extends TestCase
{
    use RefreshDatabase;

    private function ingredient(float $qty, string $unit, int $pricePiasters): Ingredient
    {
        return Ingredient::factory()->create(['purchase_quantity' => $qty, 'purchase_unit' => $unit, 'purchase_price' => $pricePiasters]);
    }

    public function test_an_item_cost_adds_up_its_ingredients_and_packaging(): void
    {
        $beef = $this->ingredient(300, 'g', 15000);   // 150 EGP for 300 g => 0.50 EGP a gram
        $bread = $this->ingredient(1, 'kg', 6000);    // 60 EGP a kilo => 0.06 EGP a gram
        $egg = $this->ingredient(30, 'pc', 18000);    // 180 EGP a tray of 30 => 6 EGP each

        $item = Item::factory()->create(['price' => 18500, 'packaging_cost' => 500]);
        $item->ingredientLines()->createMany([
            ['ingredient_id' => $beef->id, 'quantity' => 80, 'unit' => 'g'],   // 40 EGP
            ['ingredient_id' => $bread->id, 'quantity' => 150, 'unit' => 'g'], // 9 EGP
            ['ingredient_id' => $egg->id, 'quantity' => 2, 'unit' => 'pc'],    // 12 EGP
        ]);

        $summary = Costing::forItem($item->fresh());

        $this->assertEqualsWithDelta(6100, $summary['ingredients'], 0.01);
        $this->assertEqualsWithDelta(6600, $summary['total'], 0.01);          // + 5 EGP packaging
        $this->assertEqualsWithDelta(11900, $summary['profit'], 0.01);        // 185 - 66
        $this->assertEqualsWithDelta(64.32, $summary['margin'], 0.01);
        $this->assertTrue($summary['complete']);
    }

    public function test_missing_prices_and_mismatched_units_are_flagged_not_guessed(): void
    {
        $unpriced = Ingredient::factory()->create();
        $milk = $this->ingredient(1, 'l', 4000);

        $item = Item::factory()->create(['price' => 10000]);
        $item->ingredientLines()->createMany([
            ['ingredient_id' => $unpriced->id, 'quantity' => 10, 'unit' => 'g'],
            ['ingredient_id' => $milk->id, 'quantity' => 50, 'unit' => 'g'], // bought by volume
        ]);

        $summary = Costing::forItem($item->fresh());

        $this->assertSame(['unpriced', 'unit-mismatch'], array_column($summary['lines'], 'problem'));
        $this->assertFalse($summary['complete']);
        $this->assertSame(0.0, $summary['total']);
    }

    public function test_the_costs_page_lists_items_with_their_margin(): void
    {
        $this->actingAs(User::factory()->create());
        $cheese = $this->ingredient(500, 'g', 10000);
        $item = Item::factory()->create(['name_en' => 'Cheese Bagel', 'price' => 10000]);
        $item->ingredientLines()->create(['ingredient_id' => $cheese->id, 'quantity' => 100, 'unit' => 'g']); // 20 EGP

        $this->get(Costs::getUrl())->assertOk()->assertSee('Cheese Bagel')->assertSee('80%');
    }
}
