<?php

namespace App\Delivery;

use App\Models\DeliverySlot;

final class SlotOption
{
    public function __construct(
        public readonly DeliverySlot $slot,
        /** Orders it can still take; null = no limit. */
        public readonly ?int $remaining,
        public readonly bool $available,
    ) {}
}
