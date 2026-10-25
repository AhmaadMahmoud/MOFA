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
            __('Transfer the amount with InstaPay, then send us the receipt screenshot on WhatsApp. The details are below.'),
            __('Once we see the payment, we confirm your order and email you.'),
        ];
    }

    protected function status(): string
    {
        return __('Waiting for payment');
    }

    protected function payment(): ?array
    {
        $method = \App\Payments\PaymentMethods::get($this->order->payment?->method);

        return $method->viewData($this->order) + ['amount' => Money::format($this->order->total)];
    }

    protected function buttonLabel(): ?string
    {
        // The payment details are already in the email; the button opens the order page.
        return __('View your order');
    }
}
