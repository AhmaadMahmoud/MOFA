<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Payments\PaymentMethods;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The customer's order page: how to pay, where to send the receipt (WhatsApp),
 * and the status, which refreshes by itself while the order waits for Faten.
 */
class OrderPage extends Component
{
    #[Locked]
    public int $orderId;

    public function mount(Order $order): void
    {
        $this->orderId = $order->id;
    }

    public function order(): Order
    {
        return Order::query()->with(['items', 'customer', 'payment'])->findOrFail($this->orderId);
    }

    public function render()
    {
        $order = $this->order();

        return view('shop.order', [
            'order' => $order,
            'method' => PaymentMethods::get($order->payment?->method),
            // Nothing expires any more; old orders from before may still carry this status.
            'expired' => $order->status === OrderStatus::Expired,
        ]);
    }
}
