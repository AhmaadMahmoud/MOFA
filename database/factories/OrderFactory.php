<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\DeliverySlot;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => Order::newNumber(),
            'customer_id' => Customer::factory(),
            'status' => OrderStatus::Confirmed,
            'delivery_date' => now()->addDay()->toDateString(),
            'delivery_slot_id' => DeliverySlot::factory(),
            'delivery_zone_id' => null,
            'slot_label_en' => '8–10 am',
            'slot_label_ar' => '8–10 صباحًا',
            'zone_name_en' => 'Zamalek',
            'zone_name_ar' => 'الزمالك',
            'is_pickup' => false,
            'address' => '12 Street',
            'subtotal' => 19000,
            'delivery_fee' => 4000,
            'total' => 23000,
            'locale' => 'ar',
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(['status' => $status]);
    }

    /** With its first payment row, like a placed order. */
    public function withPayment(PaymentStatus $status = PaymentStatus::Pending): static
    {
        return $this->afterCreating(fn (Order $order) => $order->payments()->create([
            'method' => 'instapay',
            'amount' => $order->total,
            'status' => $status,
        ]));
    }
}
