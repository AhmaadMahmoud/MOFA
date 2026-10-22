<?php

namespace App\Payments;

use App\Models\Order;
use App\Support\Settings;

/** InstaPay transfer to Faten's address; the customer sends the receipt on WhatsApp and Faten confirms. */
final class InstapayManual implements PaymentMethod
{
    public function key(): string
    {
        return 'instapay';
    }

    public function label(): string
    {
        return 'InstaPay';
    }

    public function isReady(): bool
    {
        return Settings::hasInstapayDetails() && Settings::whatsappNumber() !== null;
    }

    public function viewData(Order $order): array
    {
        return [
            'address' => Settings::get('instapay_address'),
            'phone' => Settings::get('instapay_phone'),
            'name' => Settings::get('instapay_name'),
            'qr' => Settings::instapayQrUrl(),
            'whatsapp' => $order->whatsappReceiptUrl(),
        ];
    }
}
