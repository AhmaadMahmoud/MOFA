<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use App\Notifications\Customer\OrderConfirmed;
use App\Ordering\OrderWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use LogicException;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private OrderWorkflow $workflow;

    private User $faten;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->workflow = app(OrderWorkflow::class);
        $this->faten = User::factory()->create();
    }

    private function inReview(): Order
    {
        return Order::factory()->status(OrderStatus::PaymentReview)->withPayment(PaymentStatus::Submitted)->create();
    }

    public function test_confirming_a_payment_confirms_the_order_and_emails_the_customer(): void
    {
        // The receipt came on WhatsApp: the order is still waiting for payment when Faten confirms.
        $order = Order::factory()->status(OrderStatus::PendingPayment)->withPayment()->create();

        $this->workflow->confirmPayment($order, $this->faten);

        $order->refresh();
        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertNotNull($order->confirmed_at);
        $this->assertSame(PaymentStatus::Confirmed, $order->payment->status);
        $this->assertSame($this->faten->id, $order->payment->reviewed_by);
        Notification::assertSentTo($order->customer, OrderConfirmed::class);
    }

    public function test_a_late_payment_can_still_be_confirmed(): void
    {
        $order = Order::factory()->status(OrderStatus::Expired)->withPayment()->create();

        $this->workflow->confirmPayment($order, $this->faten);

        $this->assertSame(OrderStatus::Confirmed, $order->fresh()->status);
    }

    public function test_delivery_steps_send_no_email(): void
    {
        $order = Order::factory()->withPayment(PaymentStatus::Confirmed)->create();

        $this->workflow->markOutForDelivery($order);
        $this->assertSame(OrderStatus::OutForDelivery, $order->fresh()->status);
        Notification::assertNothingSent();

        $this->workflow->markDelivered($order->fresh());
        $this->assertSame(OrderStatus::Delivered, $order->fresh()->status);
    }

    public function test_steps_out_of_order_are_refused(): void
    {
        $order = Order::factory()->status(OrderStatus::PendingPayment)->withPayment()->create();

        $this->expectException(LogicException::class);
        $this->workflow->markOutForDelivery($order);
    }

    public function test_cancelling_an_order(): void
    {
        $order = Order::factory()->withPayment(PaymentStatus::Confirmed)->create();

        $this->workflow->cancel($order, 'Oven broke down');

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame('Oven broke down', $order->fresh()->cancel_reason);
        Notification::assertNothingSent();
    }

    public function test_nothing_cancels_unpaid_orders_automatically(): void
    {
        $order = Order::factory()->status(OrderStatus::PendingPayment)->withPayment()->create();

        $this->travel(3)->days();

        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
        $this->assertFalse(collect(\Illuminate\Support\Facades\Artisan::all())->has('orders:expire'));
    }

    public function test_customer_emails_render_in_the_order_language(): void
    {
        Notification::swap(new \Illuminate\Notifications\ChannelManager($this->app));
        $order = Order::factory()->status(OrderStatus::Confirmed)->withPayment(PaymentStatus::Confirmed)->create(['locale' => 'ar']);
        $order->items()->create(['name_en' => 'Plain Bagel', 'name_ar' => 'بيجل سادة', 'unit_price' => 4500, 'quantity' => 2, 'line_total' => 9000]);

        $html = (string) (new OrderConfirmed($order))->toMail($order->customer)->render();

        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertStringContainsString('بيجل سادة', $html);
        $this->assertStringContainsString('230 ج.م', $html);
        $this->assertStringContainsString($order->number, $html);
    }
}
