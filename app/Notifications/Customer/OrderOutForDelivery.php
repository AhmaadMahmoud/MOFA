<?php

namespace App\Notifications\Customer;

class OrderOutForDelivery extends OrderNotification
{
    protected function status(): string
    {
        return $this->order->is_pickup ? __('Ready') : __('On its way');
    }

    protected function statusColor(): string
    {
        return '#ac7884';
    }

    protected function subject(): string
    {
        return $this->order->is_pickup ? __('Your order is ready') : __('Your order is on its way');
    }

    protected function headline(): string
    {
        return $this->order->is_pickup ? __('Your order is ready for pickup.') : __('Your order is on its way to you.');
    }

    protected function lines(): array
    {
        return [__('Enjoy your morning!')];
    }

    protected function showSummary(): bool
    {
        return false;
    }
}
