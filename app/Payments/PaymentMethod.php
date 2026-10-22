<?php

namespace App\Payments;

use App\Models\Order;

/**
 * A way to pay. InstaPay (manual transfer + receipt) is the first; a card
 * gateway can be added later as another implementation without touching orders.
 */
interface PaymentMethod
{
    public function key(): string;

    public function label(): string;

    /** Whether the shop has filled in what customers need to pay this way. */
    public function isReady(): bool;

    /** What the order page shows the customer while the order waits for payment. @return array<string, mixed> */
    public function viewData(Order $order): array;
}
