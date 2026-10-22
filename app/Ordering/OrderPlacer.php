<?php

namespace App\Ordering;

use App\Delivery\DeliveryScheduler;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\DeliverySlot;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Notifications\AdminAlerts;
use App\Notifications\Customer\OrderPlaced;
use App\Payments\PaymentMethods;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class OrderPlacer
{
    /**
     * @param  array{name: string, phone: string, email: string}  $contact
     *
     * @throws SlotUnavailable when the day or slot filled up (or closed) since the customer chose it
     */
    public function place(
        Cart $cart,
        array $contact,
        string $date,
        int $slotId,
        int $zoneId,
        ?string $address,
        ?string $notes,
        string $locale,
    ): Order {
        if ($cart->isEmpty()) {
            throw new InvalidArgumentException('The cart is empty.');
        }

        $order = DB::transaction(function () use ($cart, $contact, $date, $slotId, $zoneId, $address, $notes, $locale) {
            // Lock every slot row: placements queue up one at a time, so two
            // customers can never take the last place in a slot or a day together.
            $slots = DeliverySlot::query()->lockForUpdate()->get()->keyBy('id');

            if (! (new DeliveryScheduler)->isBookable($date, $slotId)) {
                throw new SlotUnavailable;
            }

            $slot = $slots[$slotId];
            $zone = DeliveryZone::query()->active()->findOrFail($zoneId);

            $customer = Customer::query()->updateOrCreate(
                ['phone' => Customer::normalizePhone($contact['phone'])],
                ['name' => trim($contact['name']), 'email' => trim($contact['email']), 'locale' => $locale],
            );

            $subtotal = $cart->subtotal();
            $fee = $zone->fee;

            $order = Order::query()->create([
                'number' => Order::newNumber(),
                'customer_id' => $customer->id,
                'status' => OrderStatus::PendingPayment,
                'delivery_date' => $date,
                'delivery_slot_id' => $slot->id,
                'delivery_zone_id' => $zone->id,
                'slot_label_en' => $slot->label_en,
                'slot_label_ar' => $slot->label_ar,
                'zone_name_en' => $zone->name_en,
                'zone_name_ar' => $zone->name_ar,
                'is_pickup' => $zone->is_pickup,
                'address' => $zone->is_pickup ? null : trim((string) $address),
                'notes' => filled($notes) ? trim($notes) : null,
                'subtotal' => $subtotal,
                'delivery_fee' => $fee,
                'total' => $subtotal + $fee,
                'locale' => $locale,
            ]);

            foreach ($cart->lines as $line) {
                $order->items()->create([
                    'item_id' => $line->item->id,
                    'name_en' => $line->item->name_en,
                    'name_ar' => $line->item->name_ar,
                    'unit_price' => $line->item->price,
                    'quantity' => $line->quantity,
                    'line_total' => $line->total(),
                ]);
            }

            $order->payments()->create([
                'method' => PaymentMethods::DEFAULT,
                'amount' => $order->total,
                'status' => PaymentStatus::Pending,
            ]);

            return $order;
        });

        $order->customer->notify(new OrderPlaced($order));
        AdminAlerts::newOrder($order);

        return $order;
    }
}
