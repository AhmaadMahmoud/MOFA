<?php

namespace App\Notifications\Customer;

use App\Support\Money;

/** The one email a customer gets: sent when Faten confirms the payment from the dashboard. */
class OrderConfirmed extends OrderNotification
{
    protected function status(): string
    {
        return __('Confirmed and paid');
    }

    protected function statusColor(): string
    {
        return '#786b49';
    }

    protected function subject(): string
    {
        return __('Your MOFA order is confirmed');
    }

    protected function headline(): string
    {
        return __('Your order is confirmed');
    }

    protected function lines(): array
    {
        $when = [
            'date' => $this->order->delivery_date->locale($this->order->locale)->translatedFormat('l j F'),
            'slot' => $this->order->slotLabel($this->order->locale),
        ];

        return [
            __('Thank you for ordering from MOFA. We have received your payment of :amount, and your order is now confirmed.', [
                'amount' => Money::format($this->order->total),
            ]),
            $this->order->is_pickup
                ? __('Your order will be freshly baked and ready for pickup on :date, :slot.', $when)
                : __('Your order will be freshly baked and delivered to you on :date, :slot.', $when),
        ];
    }

    protected function buttonLabel(): ?string
    {
        return __('View order details');
    }
}
