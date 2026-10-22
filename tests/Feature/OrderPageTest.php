<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Livewire\OrderPage;
use App\Models\Order;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderPageTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->useLocale('en');
        Settings::put(['instapay_address' => 'mofa@instapay', 'instapay_name' => 'Faten M.', 'contact_whatsapp' => '010 9999 9999']);
        $this->order = Order::factory()->status(OrderStatus::PendingPayment)->withPayment()->create(['locale' => 'en', 'total' => 23000]);
    }

    public function test_the_page_needs_the_signed_link(): void
    {
        $this->get("/en/orders/{$this->order->number}")->assertForbidden();

        $this->get($this->order->publicUrl())
            ->assertOk()
            ->assertSee('mofa@instapay')
            ->assertSee('Send on WhatsApp')
            ->assertDontSee('Upload');
    }

    public function test_the_whatsapp_button_opens_the_shop_chat_with_the_order_written(): void
    {
        $url = $this->order->whatsappReceiptUrl();

        $this->assertStringStartsWith('https://wa.me/201099999999?text=', $url);
        $this->assertStringContainsString(rawurlencode($this->order->number), $url);
        $this->assertStringContainsString(rawurlencode('230 EGP'), $url);

        $this->get($this->order->publicUrl())->assertSee(e($url), false);
    }

    public function test_the_whatsapp_message_follows_the_order_language(): void
    {
        $this->order->update(['locale' => 'ar']);

        $this->assertStringContainsString(rawurlencode('230 ج.م'), $this->order->fresh()->whatsappReceiptUrl());
    }

    public function test_the_arabic_page_works_and_its_language_switch_stays_signed(): void
    {
        $this->get($this->order->publicUrl('ar'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee(e($this->order->publicUrl('en')), false);
    }

    public function test_a_confirmed_order_shows_its_progress_instead_of_payment_steps(): void
    {
        $this->order->update(['status' => OrderStatus::Confirmed]);

        Livewire::test(OrderPage::class, ['order' => $this->order])
            ->assertSee('Your order is confirmed!')
            ->assertDontSee('Send on WhatsApp');
    }

    public function test_an_unpaid_order_keeps_waiting_however_long_it_takes(): void
    {
        $this->travel(2)->days();

        Livewire::test(OrderPage::class, ['order' => $this->order])
            ->assertSee('Almost done: pay to confirm')
            ->assertSee('Send on WhatsApp');
    }

    public function test_a_cancelled_order_offers_to_order_again(): void
    {
        $this->order->update(['status' => OrderStatus::Cancelled]);

        Livewire::test(OrderPage::class, ['order' => $this->order])
            ->assertSee('This order was cancelled')
            ->assertSee('Order again')
            ->assertDontSee('Send on WhatsApp');
    }
}
