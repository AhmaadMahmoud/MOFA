<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\Ingredient;
use App\Models\Item;
use App\Support\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Demo data to see the shop with real MOFA photos (cropped from the Instagram posts):
 * two sandwiches with photos, nutrition and ingredients, delivery areas, and the
 * InstaPay / WhatsApp settings, so the whole order flow can be tried.
 *
 *   php artisan db:seed --class=DemoShopSeeder
 *
 * Safe to run again: items are matched by slug, and areas/settings are only added when empty.
 * PRICES AND THE FRESH START NUTRITION ARE PLACEHOLDERS: Faten sets the real ones in the dashboard.
 */
class DemoShopSeeder extends Seeder
{
    public function run(): void
    {
        $sandwiches = Category::updateOrCreate(
            ['slug' => 'sandwiches'],
            ['name_en' => 'Sandwiches', 'name_ar' => 'ساندويتشات', 'sort_order' => 1, 'is_active' => true],
        );

        $this->item($sandwiches, [
            'slug' => 'sunrise-roast',
            'name_en' => 'Sunrise Roast',
            'name_ar' => 'صن رايز روست',
            'tagline_en' => 'Roast beef version',
            'tagline_ar' => 'نسخة الروست بيف',
            'description_en' => 'A hearty morning favorite. Roast beef, fluffy scrambled eggs, sautéed mushrooms, Maasdam cheese and our house creamy spread layered in toasted sourdough.',
            'description_ar' => 'فطار مشبع ومحبوب. روست بيف، وبيض سكرامبلد، ومشروم سوتيه، وجبنة ماسدام، والسبريد الكريمي بتاعنا، كلهم في عيش ساوردو محمص.',
            'price' => 19000,
            'badge' => 'bestseller',
            'serving_size_g' => 228,
            'protein_g' => 40,
            'carbs_g' => 38,
            'fat_g' => 27,
            'fiber_g' => 3,
            'sugars_g' => 3,
            'is_featured' => true,
            'sort_order' => 1,
        ], ['sunrise-roast.jpg', 'sunrise-roast-2.jpg'], [
            ['Sourdough Bread', 'عيش ساوردو', 60, 'g'],
            ['Roast Beef', 'روست بيف', 50, 'g'],
            ['Egg', 'بيض', 1, null],
            ['Maasdam Cheese', 'جبنة ماسدام', 42, 'g'],
            ['Sautéed Mushrooms', 'مشروم سوتيه', 60, 'g'],
            ['House Creamy Spread', 'السبريد الكريمي', 15, 'g'],
        ]);

        $this->item($sandwiches, [
            'slug' => 'fresh-start',
            'name_en' => 'Fresh Start',
            'name_ar' => 'فريش ستارت',
            'tagline_en' => 'Smoked salmon, built the MOFA way',
            'tagline_ar' => 'سالمون مدخن على طريقة MOFA',
            'description_en' => 'Smoked salmon, soft-boiled eggs, fresh rocket and our herbed cream cheese on toasted multigrain sourdough. Light, fresh and filling.',
            'description_ar' => 'سالمون مدخن، وبيض مسلوق، وجرجير طازة، وكريم تشيز بالأعشاب، على عيش ساوردو بالحبوب محمص. خفيف وطازة ومشبع.',
            'price' => 22000,
            'badge' => 'new',
            // Placeholder nutrition until Faten sends the real values.
            'serving_size_g' => 240,
            'protein_g' => 32,
            'carbs_g' => 36,
            'fat_g' => 21,
            'fiber_g' => 5,
            'sugars_g' => 3,
            'is_featured' => true,
            'sort_order' => 2,
        ], ['fresh-start-1.jpg', 'fresh-start-2.jpg'], [
            ['Multigrain Sourdough', 'عيش ساوردو بالحبوب', 70, 'g'],
            ['Smoked Salmon', 'سالمون مدخن', 60, 'g'],
            ['Boiled Egg', 'بيض مسلوق', 1, null],
            ['Herbed Cream Cheese', 'كريم تشيز بالأعشاب', 30, 'g'],
            ['Rocket', 'جرجير', 10, 'g'],
        ]);

        // Somewhere to deliver, if Faten hasn't added her areas yet.
        if (! DeliveryZone::query()->exists()) {
            DeliveryZone::query()->create(['name_en' => 'Zamalek', 'name_ar' => 'الزمالك', 'fee' => 4000, 'sort_order' => 1]);
            DeliveryZone::query()->create(['name_en' => 'Maadi', 'name_ar' => 'المعادي', 'fee' => 6000, 'sort_order' => 2]);
            DeliveryZone::query()->create(['name_en' => 'Pickup from the bakery', 'name_ar' => 'استلام من المخبز', 'fee' => 0, 'is_pickup' => true, 'sort_order' => 3]);
        }

        // Payment details, only where empty: demo values to replace in "Ordering & payment".
        $demo = ['instapay_address' => 'mofa@instapay', 'instapay_name' => 'Faten', 'contact_whatsapp' => '01000000000'];
        foreach ($demo as $key => $value) {
            if (blank(Settings::get($key))) {
                Settings::put([$key => $value]);
            }
        }
    }

    /**
     * @param  list<string>  $photos  files in database/seeders/demo, copied to the public disk
     * @param  list<array{0: string, 1: string, 2: int|float, 3: ?string}>  $ingredients
     */
    private function item(Category $category, array $data, array $photos, array $ingredients): void
    {
        $paths = [];
        foreach ($photos as $photo) {
            $path = 'items/'.$photo;
            Storage::disk('public')->put($path, file_get_contents(database_path('seeders/demo/'.$photo)));
            $paths[] = $path;
        }

        $item = Item::updateOrCreate(['slug' => $data['slug']], [
            ...$data,
            'category_id' => $category->id,
            'images' => $paths,
            'is_active' => true,
        ]);

        $item->ingredientLines()->delete();
        foreach ($ingredients as $i => [$en, $ar, $quantity, $unit]) {
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
