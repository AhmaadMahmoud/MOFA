<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Customer> */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'phone' => '010'.$this->faker->unique()->numerify('########'),
            'email' => $this->faker->unique()->safeEmail(),
            'locale' => 'ar',
        ];
    }
}
