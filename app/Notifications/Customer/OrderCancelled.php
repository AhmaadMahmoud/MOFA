<?php

namespace App\Notifications\Customer;

class OrderCancelled extends OrderNotification
{
    protected function status(): string
    {
        return __('Cancelled');
    }

    protected function statusColor(): string
    {
        return '#6a4630';
    }

    protected function subject(): string
    {
        return __('Your order was cancelled');
    }

    protected function headline(): string
    {
        return __('Your order was cancelled.');
    }

    protected function lines(): array
    {
        return array_values(array_filter([
            $this->order->cancel_reason ? __('Reason: :reason', ['reason' => $this->order->cancel_reason]) : null,
            __('If you have any question, reply to this email.'),
        ]));
    }

    protected function buttonLabel(): ?string
    {
        return null;
    }
}
