<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Item;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * One real item from MOFA's Instagram (the Sunrise Roast post) so the menu
 * has something to show while Faten fills in the rest. Not run by default:
 *
 *   php artisan db:seed --class=MenuDemoSeeder
 *
 * The price was not in the post: set it in the dashboard.
 */
class MenuDemoSeeder extends Seeder
{
    public function run(): void
    {
        $sandwiches = Category::updateOrCreate(
            ['slug' => 'sandwiches'],
            ['name_en' => 'Sandwiches', 'name_ar' => 'ساندويتشات', 'sort_order' => 1, 'is_active' => true],
        );

        Storage::disk('public')->put('items/sunrise-roast.jpg', file_get_contents(database_path('seeders/demo/sunrise-roast.jpg')));

        $item = Item::updateOrCreate(['slug' => 'sunrise-roast'], [
            'category_id' => $sandwiches->id,
            'name_en' => 'Sunrise Roast',
            'name_ar' => 'صن رايز روست',
            'tagline_en' => 'Roast beef version',
            'tagline_ar' => 'نسخة الروست بيف',
            'description_en' => 'A hearty morning favorite. Roast beef, fluffy scrambled eggs, sautéed mushrooms, Maasdam cheese and our house creamy spread layered in toasted sourdough.',
            'description_ar' => 'فطار مشبع ومحبوب. روست بيف، وبيض سكرامبلد، ومشروم سوتيه، وجبنة ماسدام، والسبريد الكريمي بتاعنا، كلهم في عيش ساوردو محمص.',
            'price' => 0,
            'serving_size_g' => 228,
            'protein_g' => 40,
            'carbs_g' => 38,
            'fat_g' => 27,
            'fiber_g' => 3,
            'sugars_g' => 3,
            'images' => ['items/sunrise-roast.jpg'],
            'is_featured' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $lines = [
            ['Sourdough Bread', 'عيش ساوردو', 60, 'g'],
            ['Roast Beef', 'روست بيف', 50, 'g'],
            ['Egg', 'بيض', 1, null],
            ['Maasdam Cheese', 'جبنة ماسدام', 42, 'g'],
            ['Sautéed Mushrooms', 'مشروم سوتيه', 60, 'g'],
            ['House Creamy Spread', 'السبريد الكريمي', 15, 'g'],
        ];

        $item->ingredientLines()->delete();

        foreach ($lines as $i => [$en, $ar, $quantity, $unit]) {
            $ingredient = Ingredient::firstOrCreate(['name_en' => $en], ['name_ar' => $ar]);
            $item->ingredientLines()->create([
                'ingredient_id' => $ingredient->id,
                'quantity' => $quantity,
                'unit' => $unit,
                'sort_order' => $i + 1,
            ]);
        }
    }
}
