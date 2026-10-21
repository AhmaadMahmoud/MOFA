<?php

namespace Database\Factories;

use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Ingredient> */
class IngredientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name_en' => Str::title($this->faker->unique()->word()),
            'name_ar' => 'مكون '.$this->faker->unique()->numberBetween(1, 9999),
        ];
    }
}
