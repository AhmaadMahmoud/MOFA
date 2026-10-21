<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Item> */
class ItemFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::title($this->faker->unique()->words(2, true));

        return [
            'category_id' => Category::factory(),
            'name_en' => $name,
            'name_ar' => 'صنف '.$this->faker->unique()->numberBetween(1, 9999),
            'slug' => Str::slug($name),
            'description_en' => $this->faker->sentence(),
            'description_ar' => 'وصف قصير للصنف.',
            'price' => $this->faker->numberBetween(50, 250) * 100,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
