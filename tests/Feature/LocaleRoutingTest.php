<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_root_redirects_to_arabic_when_the_browser_language_is_not_supported(): void
    {
        $this->get('/', ['Accept-Language' => 'fr-FR,fr;q=0.9'])->assertRedirect('/ar');
    }

    public function test_root_follows_the_browser_language(): void
    {
        $this->get('/', ['Accept-Language' => 'en-US,en;q=0.9'])->assertRedirect('/en');
    }

    public function test_root_prefers_the_saved_choice_over_the_browser(): void
    {
        $this->withUnencryptedCookie('mofa_locale', 'ar')
            ->get('/', ['Accept-Language' => 'en-US,en;q=0.9'])
            ->assertRedirect('/ar');
    }

    public function test_arabic_home_is_right_to_left(): void
    {
        $this->get('/ar')
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('صباحك على مزاجك.')
            ->assertCookie('mofa_locale', 'ar', false);
    }

    public function test_english_home_is_left_to_right(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Your kind of morning.');
    }

    public function test_language_switch_points_to_the_same_page(): void
    {
        \App\Models\Item::factory()->create(['slug' => 'sunrise-roast']);
        $this->get('/en/menu/sunrise-roast')->assertOk()->assertSee('href="'.url('/ar/menu/sunrise-roast').'"', false);

        $this->get('/en/menu')->assertOk()->assertSee('href="'.url('/ar/menu').'"', false);
        $this->get('/ar/menu')->assertOk()->assertSee('href="'.url('/en/menu').'"', false);
    }

    public function test_unsupported_locale_is_not_found(): void
    {
        $this->get('/fr')->assertNotFound();
    }
}
