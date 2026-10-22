<?php

namespace App\Delivery;

use Carbon\CarbonImmutable;

/** One day a customer can pick at checkout, with its slots. */
final class DeliveryDay
{
    /** @param  list<SlotOption>  $slots */
    public function __construct(
        public readonly CarbonImmutable $date,
        public readonly array $slots,
        public readonly bool $dayFull,
    ) {}

    public function isAvailable(): bool
    {
        if ($this->dayFull) {
            return false;
        }

        foreach ($this->slots as $slot) {
            if ($slot->available) {
                return true;
            }
        }

        return false;
    }

    public function key(): string
    {
        return $this->date->toDateString();
    }
}
