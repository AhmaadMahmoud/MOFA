<?php

namespace App\Notifications\Customer;

class OrderConfirmed extends OrderNotification
{
    protected function subject(): string
    {
        return __('Your order is confirmed');
    }

    protected function headline(): string
    {
        return __('Payment received. Your order is confirmed!');
    }

    protected function lines(): array
    {
        return [__('Faten will bake it fresh for :date, :slot.', [
            'date' => $this->order->delivery_date->locale($this->order->locale)->translatedFormat('l j F'),
            'slot' => $this->order->slotLabel($this->order->locale),
        ])];
    }
}
