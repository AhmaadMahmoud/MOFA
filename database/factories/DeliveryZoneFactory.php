<?php

namespace Database\Factories;

use App\Models\DeliveryZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DeliveryZone> */
class DeliveryZoneFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name_en' => 'Zamalek',
            'name_ar' => 'الزمالك',
            'fee' => 4000,
            'is_pickup' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function pickup(): static
    {
        return $this->state(['name_en' => 'Pickup', 'name_ar' => 'استلام', 'fee' => 0, 'is_pickup' => true]);
    }
}
