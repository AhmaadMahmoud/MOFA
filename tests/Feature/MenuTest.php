<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_calories_are_computed_when_an_item_is_saved(): void
    {
        $item = Item::factory()->create(['protein_g' => 40, 'carbs_g' => 38, 'fat_g' => 27]);

        $this->assertSame(555, $item->fresh()->calories);

        $item->update(['fat_g' => 20]);
        $this->assertSame(492, $item->fresh()->calories);
    }

    public function test_menu_lists_active_items_by_category_in_both_languages(): void
    {
        $sandwiches = Category::factory()->create(['name_en' => 'Sandwiches', 'name_ar' => 'ساندويتشات', 'slug' => 'sandwiches']);
        Item::factory()->for($sandwiches)->create(['name_en' => 'Sunrise Roast', 'name_ar' => 'صن رايز روست', 'price' => 9500]);
        Item::factory()->for($sandwiches)->create(['name_en' => 'Hidden Toast', 'is_active' => false]);

        $this->get('/en/menu')->assertOk()->assertSee('Sandwiches')->assertSee('Sunrise Roast')->assertSee('95 EGP')->assertDontSee('Hidden Toast');
        $this->get('/ar/menu')->assertOk()->assertSee('ساندويتشات')->assertSee('صن رايز روست')->assertSee('95 ج.م');
    }

    public function test_categories_that_are_hidden_or_empty_are_left_out(): void
    {
        Category::factory()->create(['name_en' => 'Empty Shelf']);
        $hidden = Category::factory()->create(['name_en' => 'Hidden Shelf', 'is_active' => false]);
        Item::factory()->for($hidden)->create(['name_en' => 'Secret Bagel']);

        $this->get('/en/menu')->assertOk()->assertDontSee('Empty Shelf')->assertDontSee('Hidden Shelf')->assertDontSee('Secret Bagel');
    }

    public function test_item_page_shows_nutrition_and_ingredients(): void
    {
        $item = Item::factory()->create([
            'name_en' => 'Sunrise Roast', 'slug' => 'sunrise-roast',
            'serving_size_g' => 228, 'protein_g' => 40, 'carbs_g' => 38, 'fat_g' => 27, 'fiber_g' => 3, 'sugars_g' => 3,
        ]);
        $bread = Ingredient::factory()->create(['name_en' => 'Sourdough Bread']);
        $egg = Ingredient::factory()->create(['name_en' => 'Egg']);
        $item->ingredientLines()->create(['ingredient_id' => $bread->id, 'quantity' => 60, 'unit' => 'g', 'sort_order' => 1]);
        $item->ingredientLines()->create(['ingredient_id' => $egg->id, 'quantity' => 1, 'unit' => null, 'sort_order' => 2]);

        $this->get('/en/menu/sunrise-roast')
            ->assertOk()
            ->assertSee('Per serving of about 228 g')
            ->assertSeeInOrder(['555', 'calories', '40', 'Protein', '38', 'Carbohydrates', '27', 'Fat'])
            ->assertSee('Fiber 3 g')
            ->assertSeeInOrder(['Sourdough Bread', '60 g', 'Egg', '1'])
            ->assertDontSee('60.00');
    }

    public function test_inactive_item_page_is_not_found(): void
    {
        Item::factory()->create(['slug' => 'gone', 'is_active' => false]);

        $this->get('/en/menu/gone')->assertNotFound();
    }

    public function test_home_shows_featured_items(): void
    {
        Item::factory()->create(['name_en' => 'Bagel Box', 'is_featured' => true]);
        Item::factory()->create(['name_en' => 'Plain Toast', 'is_featured' => false]);

        $this->get('/en')->assertOk()->assertSee('Bagel Box')->assertDontSee('Plain Toast');
    }

    public function test_the_demo_shop_seeder_fills_the_menu_with_photos(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $this->seed(\Database\Seeders\DemoShopSeeder::class);
        $this->seed(\Database\Seeders\DemoShopSeeder::class); // safe to run twice

        $this->assertSame(5, Item::query()->count());
        $this->assertSame(3, Item::query()->whereHas('category', fn ($query) => $query->where('slug', 'bagels'))->count());
        $fresh = Item::query()->where('slug', 'fresh-start')->sole();
        $this->assertCount(1, $fresh->images);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($fresh->images);
        $this->assertSame(6, $fresh->ingredientLines()->count());

        $this->get('/ar/menu/fresh-start')->assertOk()->assertSee('فريش ستارت');
    }
}
