<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Pages\PrepList;
use App\Filament\Pages\ShopSettings;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\DeliverySlot;
use App\Models\Order;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AdminOrdersTest extends TestCase
{
    use RefreshDatabase;

    private User $faten;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Notification::fake();
        $this->faten = User::factory()->create();
        $this->actingAs($this->faten);
    }

    public function test_order_and_delivery_screens_load(): void
    {
        $order = Order::factory()->withPayment()->create();

        foreach (['/admin', '/admin/orders', '/admin/customers', '/admin/prep-list', '/admin/delivery-zones', '/admin/delivery-slots', '/admin/closed-dates', '/admin/settings'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get("/admin/orders/{$order->getRouteKey()}")->assertOk()->assertSee($order->number);
    }

    public function test_the_screens_load_in_arabic(): void
    {
        $order = Order::factory()->withPayment()->create();
        $this->get('/admin/locale/ar');

        $this->get('/admin/orders')->assertOk()->assertSee('dir="rtl"', false);
        $this->get("/admin/orders/{$order->getRouteKey()}")->assertOk();
        $this->get('/admin/prep-list')->assertOk();
        $this->get('/admin/settings')->assertOk();
    }

    public function test_confirming_a_payment_from_the_order_screen(): void
    {
        $order = Order::factory()->status(OrderStatus::PendingPayment)->withPayment()->create();

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('confirmPayment')
            ->assertHasNoActionErrors();

        $this->assertSame(OrderStatus::Confirmed, $order->fresh()->status);
    }

    public function test_settings_are_saved(): void
    {
        Livewire::test(ShopSettings::class)
            ->set('data.order_cutoff', '19:00')
            ->set('data.max_days_ahead', 5)
            ->set('data.daily_capacity', '40')
            ->set('data.days_off', ['5'])
            ->set('data.instapay_address', 'mofa@instapay')
            ->call('save')
            ->assertHasNoErrors();

        Settings::flush();
        $this->assertSame('19:00', Settings::cutoff());
        $this->assertSame(5, Settings::maxDaysAhead());
        $this->assertSame(40, Settings::dailyCapacity());
        $this->assertSame([5], Settings::daysOff());
        $this->assertSame('mofa@instapay', Settings::get('instapay_address'));
    }

    public function test_the_prep_list_adds_up_confirmed_items(): void
    {
        $tomorrow = now()->addDay()->toDateString();
        $slot = DeliverySlot::query()->first();
        $make = function (OrderStatus $status, int $bagels) use ($tomorrow, $slot) {
            $o = Order::factory()->status($status)->create(['delivery_date' => $tomorrow, 'delivery_slot_id' => $slot->id]);
            $o->items()->create(['name_en' => 'Plain Bagel', 'name_ar' => 'بيجل', 'unit_price' => 4500, 'quantity' => $bagels, 'line_total' => 4500 * $bagels]);
        };
        $make(OrderStatus::Confirmed, 3);
        $make(OrderStatus::Confirmed, 2);
        $make(OrderStatus::PendingPayment, 4);
        $make(OrderStatus::Cancelled, 9);

        $this->useLocale('en');
        $page = Livewire::test(PrepList::class)->assertSet('date', $tomorrow);
        $totals = $page->instance()->totals();

        $this->assertSame([['name' => 'Plain Bagel', 'confirmed' => 5, 'review' => 4]], $totals->all());
        $page->assertSee('Plain Bagel')->assertSee('+4');
    }
}
