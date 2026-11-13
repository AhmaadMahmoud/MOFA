<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Ingredients\Pages\ManageIngredients;
use App\Filament\Widgets\LowStock;
use App\Models\Ingredient;
use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use App\Ordering\OrderWorkflow;
use App\Support\Stock;
use Filament\Notifications\DatabaseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    private OrderWorkflow $workflow;

    private User $faten;

    private Ingredient $beef;   // kept in kg, used in g

    private Ingredient $eggs;   // kept in pieces, a bare count in the recipe

    private Ingredient $salt;   // not tracked

    private Item $sunrise;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->workflow = app(OrderWorkflow::class);
        $this->faten = User::factory()->create();

        $this->beef = Ingredient::factory()->create(['stock_quantity' => 2, 'stock_unit' => 'kg', 'low_stock_at' => 0.5]);
        $this->eggs = Ingredient::factory()->create(['stock_quantity' => 30, 'stock_unit' => 'pc']);
        $this->salt = Ingredient::factory()->create();

        $this->sunrise = Item::factory()->create();
        $this->sunrise->ingredientLines()->createMany([
            ['ingredient_id' => $this->beef->id, 'quantity' => 50, 'unit' => 'g'],
            ['ingredient_id' => $this->eggs->id, 'quantity' => 1, 'unit' => null],
            ['ingredient_id' => $this->salt->id, 'quantity' => 2, 'unit' => 'g'],
        ]);
    }

    private function orderOf(Item $item, int $quantity, OrderStatus $status = OrderStatus::PendingPayment): Order
    {
        $order = Order::factory()->status($status)->withPayment()->create();
        $order->items()->create([
            'item_id' => $item->id, 'name_en' => 'Sunrise', 'name_ar' => 'صن رايز',
            'unit_price' => 19000, 'quantity' => $quantity, 'line_total' => 19000 * $quantity,
        ]);

        return $order;
    }

    public function test_confirming_a_payment_takes_the_ingredients_out_of_stock(): void
    {
        $order = $this->orderOf($this->sunrise, 3);

        $this->workflow->confirmPayment($order, $this->faten);

        $this->assertEqualsWithDelta(1.85, (float) $this->beef->fresh()->stock_quantity, 0.0001); // 2 kg - 3 × 50 g
        $this->assertEqualsWithDelta(27, (float) $this->eggs->fresh()->stock_quantity, 0.0001);   // 30 - 3
        $this->assertNull($this->salt->fresh()->stock_quantity);                                   // not tracked
        $this->assertCount(2, $order->fresh()->stock_taken);
    }

    public function test_an_order_that_is_not_confirmed_takes_nothing(): void
    {
        $this->orderOf($this->sunrise, 3);

        $this->assertEqualsWithDelta(2, (float) $this->beef->fresh()->stock_quantity, 0.0001);
    }

    public function test_stock_is_taken_only_once_per_order(): void
    {
        $order = $this->orderOf($this->sunrise, 2);

        $this->workflow->confirmPayment($order, $this->faten);
        Stock::takeFor($order->fresh());

        $this->assertEqualsWithDelta(28, (float) $this->eggs->fresh()->stock_quantity, 0.0001);
    }

    public function test_cancelling_a_confirmed_order_puts_back_what_it_took(): void
    {
        $order = $this->orderOf($this->sunrise, 4);
        $this->workflow->confirmPayment($order, $this->faten);

        // The recipe changes afterwards: the return still matches what was taken.
        $this->sunrise->ingredientLines()->where('ingredient_id', $this->beef->id)->update(['quantity' => 80]);
        $this->workflow->cancel($order->fresh(), 'Customer asked');

        $this->assertEqualsWithDelta(2, (float) $this->beef->fresh()->stock_quantity, 0.0001);
        $this->assertEqualsWithDelta(30, (float) $this->eggs->fresh()->stock_quantity, 0.0001);
        $this->assertNull($order->fresh()->stock_taken);
    }

    public function test_cancelling_an_unpaid_order_changes_no_stock(): void
    {
        $order = $this->orderOf($this->sunrise, 2);

        $this->workflow->cancel($order, null);

        $this->assertEqualsWithDelta(30, (float) $this->eggs->fresh()->stock_quantity, 0.0001);
    }

    public function test_the_return_follows_a_change_of_stock_unit(): void
    {
        $order = $this->orderOf($this->sunrise, 2);
        $this->workflow->confirmPayment($order, $this->faten);   // takes 0.1 kg

        $this->beef->update(['stock_quantity' => 1900, 'stock_unit' => 'g']);
        $this->workflow->cancel($order->fresh(), null);

        $this->assertEqualsWithDelta(2000, (float) $this->beef->fresh()->stock_quantity, 0.0001);
    }

    public function test_a_line_in_a_unit_that_cannot_become_the_stock_unit_is_skipped(): void
    {
        $this->eggs->update(['stock_unit' => 'kg', 'stock_quantity' => 3]);   // eggs by weight, recipe counts pieces

        $this->workflow->confirmPayment($this->orderOf($this->sunrise, 2), $this->faten);

        $this->assertEqualsWithDelta(3, (float) $this->eggs->fresh()->stock_quantity, 0.0001);
    }

    public function test_the_bell_rings_once_when_an_ingredient_runs_low(): void
    {
        // 2 kg, warn at 0.5 kg: 30 sandwiches (1.5 kg) cross the line, the next order doesn't ring again.
        $this->eggs->update(['stock_quantity' => 100]);   // plenty, so only the beef rings
        $this->workflow->confirmPayment($this->orderOf($this->sunrise, 30), $this->faten);
        $this->workflow->confirmPayment($this->orderOf($this->sunrise, 1), $this->faten);

        $this->assertTrue(Stock::isLow($this->beef->fresh()));
        Notification::assertSentToTimes($this->faten, DatabaseNotification::class, 1);
    }

    public function test_stock_can_go_below_zero(): void
    {
        $this->eggs->update(['stock_quantity' => 1]);

        $this->workflow->confirmPayment($this->orderOf($this->sunrise, 3), $this->faten);

        $this->assertEqualsWithDelta(-2, (float) $this->eggs->fresh()->stock_quantity, 0.0001);
        $this->assertSame([$this->eggs->id], Ingredient::query()->lowOnStock()->pluck('id')->all());   // beef still has 1.85 kg
    }

    public function test_faten_can_add_stock_from_the_ingredients_page(): void
    {
        $this->actingAs($this->faten);

        Livewire::test(ManageIngredients::class)
            ->callTableAction('restock', $this->eggs, ['amount' => 12])
            ->assertHasNoTableActionErrors();

        $this->assertEqualsWithDelta(42, (float) $this->eggs->fresh()->stock_quantity, 0.0001);
    }

    public function test_the_dashboard_lists_what_is_running_low(): void
    {
        $this->actingAs($this->faten);
        $this->eggs->update(['stock_quantity' => 0]);

        $this->get(Dashboard::getUrl())->assertOk()->assertSee(__('Running low'));
        Livewire::test(LowStock::class)
            ->assertCanSeeTableRecords([$this->eggs])
            ->assertCanNotSeeTableRecords([$this->beef]);
        Livewire::test(ManageIngredients::class)
            ->assertOk()
            ->filterTable('low')
            ->assertCanSeeTableRecords([$this->eggs])
            ->assertCanNotSeeTableRecords([$this->beef, $this->salt]);
    }
}
