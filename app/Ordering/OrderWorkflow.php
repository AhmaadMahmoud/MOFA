<?php

namespace App\Ordering;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use App\Notifications\Customer\OrderCancelled;
use App\Notifications\Customer\OrderConfirmed;
use App\Notifications\Customer\OrderOutForDelivery;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Every status change of an order, in one place:
 *
 * pending_payment → confirmed → out_for_delivery → delivered
 *                 ↘ cancelled (by Faten, from any open status)
 *
 * The customer sends the InstaPay receipt on WhatsApp; Faten checks it and confirms here.
 * Nothing is cancelled automatically: an unpaid order waits until Faten confirms or cancels it.
 * (Old orders may still be "expired" from before; they can be confirmed too.)
 */
final class OrderWorkflow
{
    public function confirmPayment(Order $order, User $by): void
    {
        $this->guard($order, [OrderStatus::PaymentReview, OrderStatus::PendingPayment, OrderStatus::Expired]);

        DB::transaction(function () use ($order, $by) {
            $order->payment->update([
                'status' => PaymentStatus::Confirmed,
                'reviewed_by' => $by->id,
                'reviewed_at' => now(),
            ]);
            $order->update(['status' => OrderStatus::Confirmed, 'confirmed_at' => now()]);
        });

        $order->customer->notify(new OrderConfirmed($order));
    }

    public function markOutForDelivery(Order $order): void
    {
        $this->guard($order, [OrderStatus::Confirmed]);
        $order->update(['status' => OrderStatus::OutForDelivery]);
        $order->customer->notify(new OrderOutForDelivery($order));
    }

    public function markDelivered(Order $order): void
    {
        $this->guard($order, [OrderStatus::Confirmed, OrderStatus::OutForDelivery]);
        $order->update(['status' => OrderStatus::Delivered]);
    }

    public function cancel(Order $order, ?string $reason): void
    {
        if (! $order->status->isOpen()) {
            throw new LogicException('Only open orders can be cancelled.');
        }

        $order->update([
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
            'cancel_reason' => filled($reason) ? $reason : null,
        ]);
        $order->customer->notify(new OrderCancelled($order));
    }

    private function guard(Order $order, array $allowed): void
    {
        if (! in_array($order->status, $allowed, true)) {
            throw new LogicException("Order {$order->number} is {$order->status->value}.");
        }
    }
}
