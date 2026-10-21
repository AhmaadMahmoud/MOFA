<?php

namespace Tests\Feature;

use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Filament\Resources\Items\Pages\CreateItem;
use App\Filament\Resources\Items\Pages\EditItem;
use App\Filament\Resources\Items\Pages\ListItems;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::factory()->create());
    }

    public function test_dashboard_is_english_by_default(): void
    {
        $this->get('/admin')->assertOk()->assertSee('<html', false)->assertSee('lang="en"', false)->assertSee('dir="ltr"', false);
    }

    public function test_dashboard_switches_to_arabic_and_back(): void
    {
        $this->get('/admin/locale/ar')->assertRedirect();
        $this->get('/admin/items')->assertOk()->assertSee('lang="ar"', false)->assertSee('dir="rtl"', false);

        $this->get('/admin/locale/en')->assertRedirect();
        $this->get('/admin/items')->assertOk()->assertSee('lang="en"', false);
    }

    public function test_the_public_site_language_does_not_change_the_dashboard(): void
    {
        $this->get('/ar')->assertOk();
        $this->get('/admin')->assertOk()->assertSee('lang="en"', false);
    }

    public function test_menu_screens_load(): void
    {
        $item = Item::factory()->create();

        $this->get('/admin/categories')->assertOk();
        $this->get('/admin/ingredients')->assertOk();
        $this->get('/admin/items')->assertOk()->assertSee($item->name_en);
        $this->get("/admin/items/{$item->getRouteKey()}/edit")->assertOk();
        $this->get('/admin/items/create')->assertOk();
    }

    public function test_creating_an_item_stores_piasters_and_computes_calories(): void
    {
        $category = Category::factory()->create();
        $bread = Ingredient::factory()->create(['name_en' => 'Sourdough Bread']);

        Livewire::test(CreateItem::class)
            ->fillForm([
                'name_en' => 'Sunrise Roast',
                'name_ar' => 'صن رايز روست',
                'slug' => 'sunrise-roast',
                'category_id' => $category->id,
                'price' => '95.5',
                'protein_g' => 40,
                'carbs_g' => 38,
                'fat_g' => 27,
                'ingredientLines' => [
                    ['ingredient_id' => $bread->id, 'quantity' => 60, 'unit' => 'g'],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $item = Item::where('slug', 'sunrise-roast')->firstOrFail();
        $this->assertSame(9550, $item->price);
        $this->assertSame(555, $item->calories);
        $this->assertSame('Sourdough Bread', $item->ingredientLines->first()->ingredient->name_en);
        $this->assertEquals(60, $item->ingredientLines->first()->quantity);
    }

    public function test_editing_shows_the_price_in_pounds(): void
    {
        $item = Item::factory()->create(['price' => 9550]);

        Livewire::test(EditItem::class, ['record' => $item->getRouteKey()])
            ->assertFormSet(['price' => 95.5]);
    }

    public function test_required_fields_are_enforced(): void
    {
        Livewire::test(CreateItem::class)
            ->fillForm(['name_en' => '', 'name_ar' => '', 'price' => null])
            ->call('create')
            ->assertHasFormErrors(['name_en' => 'required', 'name_ar' => 'required', 'price' => 'required', 'category_id' => 'required']);
    }

    public function test_items_list_and_categories_render_in_livewire(): void
    {
        Item::factory()->count(3)->create();

        Livewire::test(ListItems::class)->assertCanSeeTableRecords(Item::all());
        Livewire::test(ManageCategories::class)->assertSuccessful();
    }

    public function test_home_lists_items_that_need_attention(): void
    {
        Item::factory()->create(['name_en' => 'Plain Bagel', 'price' => 0]);
        Item::factory()->create([
            'name_en' => 'Complete Box', 'price' => 12000, 'images' => ['items/box.jpg'],
            'protein_g' => 10, 'carbs_g' => 20, 'fat_g' => 5, 'description_en' => 'x', 'description_ar' => 'س',
        ]);

        Livewire::test(\App\Filament\Widgets\ItemsNeedingAttention::class)
            ->assertCanSeeTableRecords(Item::where('name_en', 'Plain Bagel')->get())
            ->assertCanNotSeeTableRecords(Item::where('name_en', 'Complete Box')->get());
    }

    public function test_arabic_items_table_keeps_numbers_and_units_in_order(): void
    {
        $category = Category::factory()->create(['name_en' => 'Sandwiches', 'name_ar' => 'ساندويتشات']);
        Item::factory()->for($category)->create(['price' => 9500, 'protein_g' => 40, 'carbs_g' => 38, 'fat_g' => 27]);

        $this->get('/admin/locale/ar');
        $this->get('/admin/items')
            ->assertOk()
            ->assertSee('555 سعر حراري')
            ->assertSee('95 ج.م')
            ->assertSee('ساندويتشات')
            ->assertDontSee('555 kcal');
    }
}
