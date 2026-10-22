<?php

namespace Database\Factories;

use App\Models\DeliverySlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DeliverySlot> */
class DeliverySlotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'label_en' => '8–10 am',
            'label_ar' => '8–10 صباحًا',
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'capacity' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
