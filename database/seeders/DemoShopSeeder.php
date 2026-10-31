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
 * The menu from MOFA's posts (photos cropped from them, without the text): Sunrise Roast and
 * Fresh Start on sourdough and on a bagel, plus a roast beef Sunrise bagel, with nutrition and
 * ingredients; delivery areas; and the InstaPay / WhatsApp settings, so the whole order flow can be tried.
 *
 *   php artisan db:seed --class=DemoShopSeeder
 *
 * Safe to run again: items are matched by slug, and areas/settings are only added when empty.
 * PRICES AND NUTRITION ARE PLACEHOLDERS: Faten sets the real ones in the dashboard.
 */
class DemoShopSeeder extends Seeder
{
    public function run(): void
    {
        $sandwiches = Category::updateOrCreate(
            ['slug' => 'sandwiches'],
            ['name_en' => 'Sourdough Sandwiches', 'name_ar' => 'ساندويتشات الساوردو', 'sort_order' => 1, 'is_active' => true],
        );
        $bagels = Category::updateOrCreate(
            ['slug' => 'bagels'],
            ['name_en' => 'Bagels', 'name_ar' => 'البيجل', 'sort_order' => 2, 'is_active' => true],
        );

        // Sunrise Roast, from the posts: roast turkey on sourdough or a bagel, and a roast beef bagel.
        $sunrise = [
            'description_en' => 'Roast turkey, Maasdam cheese, sautéed mushrooms, fluffy scrambled eggs and our cream cheese mayo, layered in toasted sourdough.',
            'description_ar' => 'شرائح ديك رومي مشوي، وجبن ماسدام، وفطر سوتيه، وبيض مخفوق، وصلصة الكريم تشيز بالمايونيز، بين شريحتين من خبز الساوردو المحمّص.',
            'serving_size_g' => 228, 'protein_g' => 38, 'carbs_g' => 38, 'fat_g' => 25, 'fiber_g' => 3, 'sugars_g' => 3,
        ];
        $sunriseIngredients = [
            ['Roast Turkey', 'ديك رومي مشوي', 50, 'g'],
            ['Maasdam Cheese', 'جبن ماسدام', 42, 'g'],
            ['Sautéed Mushrooms', 'فطر سوتيه', 60, 'g'],
            ['Scrambled Eggs', 'بيض مخفوق', 1, null],
            ['Cream Cheese Mayo', 'صلصة الكريم تشيز بالمايونيز', 15, 'g'],
        ];

        $this->item($sandwiches, [
            ...$sunrise,
            'slug' => 'sunrise-roast',
            'name_en' => 'Sunrise Roast',
            'name_ar' => 'صن رايز روست',
            'tagline_en' => 'Melt into your morning',
            'tagline_ar' => 'صباحٌ يذوب دفئًا',
            'price' => 19000,
            'badge' => 'bestseller',
            'is_featured' => true,
            'sort_order' => 1,
        ], ['sunrise-roast.jpg'], [['Sourdough Bread', 'خبز الساوردو', 60, 'g'], ...$sunriseIngredients]);

        $this->item($bagels, [
            ...$sunrise,
            'slug' => 'sunrise-roast-bagel',
            'name_en' => 'Sunrise Roast Bagel',
            'name_ar' => 'صن رايز روست بيجل',
            'tagline_en' => 'Same great taste, new shape',
            'tagline_ar' => 'المذاق نفسه، بشكلٍ جديد',
            'description_en' => 'Roast turkey, Maasdam cheese, sautéed mushrooms, fluffy scrambled eggs and our cream cheese mayo in a fresh sourdough bagel.',
            'description_ar' => 'شرائح ديك رومي مشوي، وجبن ماسدام، وفطر سوتيه، وبيض مخفوق، وصلصة الكريم تشيز بالمايونيز، في خبز بيجل الساوردو الطازج.',
            'price' => 19000,
            'badge' => 'new',
            'is_featured' => true,
            'sort_order' => 1,
        ], ['sunrise-roast-bagel.jpg'], [['Sourdough Bagel', 'خبز بيجل الساوردو', 90, 'g'], ...$sunriseIngredients]);

        $this->item($bagels, [
            ...$sunrise,
            'slug' => 'sunrise-roast-beef-bagel',
            'name_en' => 'Sunrise Roast Beef Bagel',
            'name_ar' => 'صن رايز روست بيف بيجل',
            'tagline_en' => 'Same great taste, new shape',
            'tagline_ar' => 'المذاق نفسه، بشكلٍ جديد',
            'description_en' => 'Roast beef, Maasdam cheese, sautéed mushrooms, fluffy scrambled eggs and our cream cheese mayo in a fresh sourdough bagel.',
            'description_ar' => 'شرائح لحم بقري مشوي، وجبن ماسدام، وفطر سوتيه، وبيض مخفوق، وصلصة الكريم تشيز بالمايونيز، في خبز بيجل الساوردو الطازج.',
            'protein_g' => 40, 'fat_g' => 27,
            'price' => 19000,
            'badge' => 'new',
            'sort_order' => 2,
        ], ['sunrise-roast-beef-bagel.jpg'], [
            ['Sourdough Bagel', 'خبز بيجل الساوردو', 90, 'g'],
            ['Roast Beef', 'لحم بقري مشوي', 50, 'g'],
            ...array_slice($sunriseIngredients, 1),
        ]);

        // Fresh Start, on sourdough and on a bagel.
        $fresh = [
            'tagline_en' => 'A fresh start to your morning',
            'tagline_ar' => 'بدايةٌ منعشة لصباحك',
            'serving_size_g' => 240, 'protein_g' => 32, 'carbs_g' => 36, 'fat_g' => 21, 'fiber_g' => 5, 'sugars_g' => 3,
        ];
        $freshIngredients = [
            ['Smoked Salmon', 'سلمون مدخّن', 60, 'g'],
            ['Labneh', 'لبنة', 30, 'g'],
            ['Soft Boiled Eggs', 'بيض مسلوق', 1, null],
            ['Rocket', 'جرجير', 10, 'g'],
            ['Cream Cheese Mayo', 'صلصة الكريم تشيز بالمايونيز', 15, 'g'],
        ];

        $this->item($sandwiches, [
            ...$fresh,
            'slug' => 'fresh-start',
            'name_en' => 'Fresh Start',
            'name_ar' => 'فريش ستارت',
            'description_en' => 'Smoked salmon, labneh, soft boiled eggs, fresh rocket and our cream cheese mayo on toasted sourdough. Light, fresh and filling.',
            'description_ar' => 'سلمون مدخّن، ولبنة، وبيض مسلوق، وجرجير طازج، وصلصة الكريم تشيز بالمايونيز، على خبز الساوردو المحمّص. خفيف ومنعش ومُشبع.',
            'price' => 22000,
            'badge' => 'new',
            'is_featured' => true,
            'sort_order' => 2,
        ], ['fresh-start.jpg'], [['Sourdough Bread', 'خبز الساوردو', 70, 'g'], ...$freshIngredients]);

        $this->item($bagels, [
            ...$fresh,
            'slug' => 'fresh-start-bagel',
            'name_en' => 'Fresh Start Bagel',
            'name_ar' => 'فريش ستارت بيجل',
            'description_en' => 'Smoked salmon, labneh, soft boiled eggs, fresh rocket and our cream cheese mayo in a fresh sourdough bagel.',
            'description_ar' => 'سلمون مدخّن، ولبنة، وبيض مسلوق، وجرجير طازج، وصلصة الكريم تشيز بالمايونيز، في خبز بيجل الساوردو الطازج.',
            'price' => 22000,
            'badge' => 'new',
            'sort_order' => 3,
        ], ['fresh-start-bagel.jpg'], [['Sourdough Bagel', 'خبز بيجل الساوردو', 90, 'g'], ...$freshIngredients]);

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
            $ingredient = Ingredient::updateOrCreate(['name_en' => $en], ['name_ar' => $ar]);
            $item->ingredientLines()->create([
                'ingredient_id' => $ingredient->id,
                'quantity' => $quantity,
                'unit' => $unit,
                'sort_order' => $i + 1,
            ]);
        }
    }
}
