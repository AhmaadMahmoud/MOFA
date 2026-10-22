<?php

namespace App\Notifications\Customer;

use App\Support\Money;

class OrderPlaced extends OrderNotification
{
    protected function subject(): string
    {
        return __('We got your order');
    }

    protected function headline(): string
    {
        return __('We got your order. One step left: the payment.');
    }

    protected function lines(): array
    {
        return [
            __('Send :amount by InstaPay, then send us the receipt screenshot on WhatsApp. Your order page has the details and a button that opens the chat.', ['amount' => Money::format($this->order->total)]),
            __('Once we see the payment, we confirm your order and email you.'),
        ];
    }

    protected function buttonLabel(): ?string
    {
        return __('How to pay');
    }
}
