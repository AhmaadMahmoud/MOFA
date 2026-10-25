<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Livewire\Checkout;
use App\Models\Customer;
use App\Models\DeliverySlot;
use App\Models\DeliveryZone;
use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Item $bagel;

    private Item $roast;

    private DeliveryZone $zone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-10-21 10:00', 'Africa/Cairo'));
        $this->useLocale('en');

        Settings::put(['instapay_address' => 'mofa@instapay', 'contact_whatsapp' => '01099999999']);
        $this->zone = DeliveryZone::factory()->create(['fee' => 4000]);
        $this->bagel = Item::factory()->create(['name_en' => 'Plain Bagel', 'price' => 4500]);
        $this->roast = Item::factory()->create(['name_en' => 'Sunrise Roast', 'price' => 19000]);
        User::factory()->create();
    }

    private function filled()
    {
        return Livewire::test(Checkout::class)
            ->call('loadCart', [$this->bagel->id => 2, $this->roast->id => 1])
            ->set('name', 'Mona Adel')
            ->set('phone', '+20 101 234 5678')
            ->set('email', 'mona@example.com')
            ->call('next')
            ->assertHasNoErrors()
            ->assertSet('step', 2)
            ->set('zoneId', $this->zone->id)
            ->set('address', '12 Shagaret El Dor, Zamalek')
            ->call('next')
            ->assertHasNoErrors()
            ->assertSet('step', 3);
    }

    public function test_the_checkout_page_loads_in_both_languages(): void
    {
        $this->get('/en/checkout')->assertOk()->assertSee('noindex', false);
        $this->get('/ar/checkout')->assertOk()->assertSee('dir="rtl"', false);
    }

    public function test_it_preselects_the_first_open_day_and_slot(): void
    {
        Livewire::test(Checkout::class)
            ->assertSet('date', '2026-10-22')
            ->assertSet('slotId', DeliverySlot::query()->ordered()->first()->id)
            ->assertSet('zoneId', $this->zone->id);
    }

    public function test_placing_an_order_prices_it_on_the_server(): void
    {
        Notification::fake();

        $this->filled()->call('place')->assertHasNoErrors()->assertRedirect();

        $order = Order::query()->with('items', 'payment', 'customer')->sole();
        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertSame(4500 * 2 + 19000, $order->subtotal);
        $this->assertSame(4000, $order->delivery_fee);
        $this->assertSame(4500 * 2 + 19000 + 4000, $order->total);
        $this->assertSame('2026-10-22', $order->delivery_date->toDateString());
        $this->assertSame('Zamalek', $order->zone_name_en);
        $this->assertSame('8–10 am', $order->slot_label_en);
        $this->assertSame('en', $order->locale);
        $this->assertNull($order->expires_at, 'nothing expires: Faten confirms or cancels');
        $this->assertCount(2, $order->items);
        $this->assertSame(PaymentStatus::Pending, $order->payment->status);
        $this->assertSame($order->total, $order->payment->amount);
        // Phone stored in one shape.
        $this->assertSame('01012345678', $order->customer->phone);

        // No email when ordering: the customer only hears from us once Faten confirms.
        Notification::assertNotSentTo($order->customer, \App\Notifications\Customer\OrderConfirmed::class);
        Notification::assertSentTo(User::query()->first(), \Filament\Notifications\DatabaseNotification::class);
    }

    public function test_the_same_phone_is_the_same_customer(): void
    {
        Customer::factory()->create(['phone' => '01012345678', 'name' => 'Old name']);

        $this->filled()->call('place');

        $this->assertSame(1, Customer::query()->count());
        $this->assertSame('Mona Adel', Customer::query()->first()->name);
    }

    public function test_hidden_and_unpriced_items_are_dropped_from_the_cart(): void
    {
        $hidden = Item::factory()->create(['is_active' => false]);
        $unpriced = Item::factory()->create(['price' => 0]);

        $component = Livewire::test(Checkout::class)
            ->call('loadCart', [$this->bagel->id => 1, $hidden->id => 1, $unpriced->id => 3, 99999 => 1, $this->roast->id => 500]);

        $this->assertSame([$this->bagel->id => 1, $this->roast->id => 20], $component->get('quantities'));
        $component->assertDispatched('cart-synced');
    }

    public function test_details_are_validated(): void
    {
        Livewire::test(Checkout::class)
            ->call('loadCart', [$this->bagel->id => 1])
            ->set('name', '')
            ->set('phone', '12345')
            ->set('email', 'not-an-email')
            ->call('next')
            ->assertHasErrors(['name', 'phone', 'email'])
            ->assertSet('step', 1);
    }

    public function test_an_address_is_needed_except_for_pickup(): void
    {
        $pickup = DeliveryZone::factory()->pickup()->create();

        $base = Livewire::test(Checkout::class)
            ->call('loadCart', [$this->bagel->id => 1])
            ->set('name', 'Mona')->set('phone', '01012345678')->set('email', 'mona@example.com')
            ->call('next');

        $base->set('zoneId', $this->zone->id)->set('address', '')->call('next')->assertHasErrors('address');
        $base->set('zoneId', $pickup->id)->call('next')->assertHasNoErrors()->assertSet('step', 3);
    }

    public function test_a_slot_that_fills_up_meanwhile_sends_the_customer_back(): void
    {
        $component = $this->filled();

        $slot = DeliverySlot::query()->find($component->get('slotId'));
        $slot->update(['capacity' => 1]);
        Order::factory()->create(['delivery_date' => '2026-10-22', 'delivery_slot_id' => $slot->id]);

        $component->call('place')
            ->assertHasErrors('slotId')
            ->assertSet('step', 2)
            ->assertNoRedirect();
        // Moved to the next open slot that day.
        $this->assertNotSame($slot->id, $component->get('slotId'));
        $this->assertSame(1, Order::query()->count());
    }

    public function test_ordering_is_closed_without_delivery_areas_instapay_details_or_whatsapp(): void
    {
        $this->zone->update(['is_active' => false]);
        Livewire::test(Checkout::class)->call('loadCart', [$this->bagel->id => 1])->assertSee('Online ordering opens soon');

        $this->zone->update(['is_active' => true]);
        Settings::put(['instapay_address' => null]);
        Livewire::test(Checkout::class)->call('loadCart', [$this->bagel->id => 1])->assertSee('Online ordering opens soon');

        // No WhatsApp number: nowhere to send the receipt.
        Settings::put(['instapay_address' => 'mofa@instapay', 'contact_whatsapp' => null]);
        Livewire::test(Checkout::class)->call('loadCart', [$this->bagel->id => 1])->assertSee('Online ordering opens soon');
    }

    public function test_an_empty_cart_shows_the_menu_link(): void
    {
        Livewire::test(Checkout::class)->call('loadCart', [])->assertSee('Your order is empty.');
    }

    public function test_phone_numbers_are_normalized(): void
    {
        foreach (['01012345678', '+201012345678', '00201012345678', '010 1234 5678', '١٠١٢٣٤٥٦٧٨'] as $input) {
            $this->assertTrue(Customer::isValidPhone($input), $input);
        }
        $this->assertSame('01012345678', Customer::normalizePhone('١٠١٢٣٤٥٦٧٨'));
        $this->assertFalse(Customer::isValidPhone('0221234567'), 'landline');
        $this->assertFalse(Customer::isValidPhone('0101234567'), 'too short');
    }
}
