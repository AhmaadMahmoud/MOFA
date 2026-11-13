<?php

namespace Tests\Feature;

use App\Analytics\Report;
use App\Analytics\Source;
use App\Analytics\Tracker;
use App\Analytics\UserAgent;
use App\Filament\Pages\Visitors;
use App\Filament\Widgets\Visitors\BusyHours;
use App\Filament\Widgets\Visitors\PagesAndDevices;
use App\Filament\Widgets\Visitors\PopularItems;
use App\Filament\Widgets\Visitors\RecentVisits;
use App\Filament\Widgets\Visitors\TrafficSources;
use App\Filament\Widgets\Visitors\VisitorFunnel;
use App\Filament\Widgets\Visitors\VisitorStats;
use App\Filament\Widgets\Visitors\VisitorsTrend;
use App\Models\Category;
use App\Models\Item;
use App\Models\Order;
use App\Models\PageView;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1';

    private const INSTAGRAM = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Instagram 350.0.0.0 (iPhone15,2; iOS 18_0; ar_EG)';

    private Item $roast;

    protected function setUp(): void
    {
        parent::setUp();
        $category = Category::factory()->create();
        $this->roast = Item::factory()->for($category)->create(['slug' => 'sunrise-roast']);
    }

    private function browse(string $uri, array $headers = [])
    {
        return $this->withHeaders(['User-Agent' => self::PHONE] + $headers)->get($uri);
    }

    public function test_a_page_view_starts_an_anonymous_visit(): void
    {
        $this->browse('/ar')->assertOk()->assertCookie(Tracker::COOKIE);

        $visit = Visit::query()->sole();
        $this->assertSame('mobile', $visit->device);
        $this->assertSame('Safari', $visit->browser);
        $this->assertSame('direct', $visit->source);
        $this->assertSame('/ar', $visit->landing_path);
        $this->assertSame(1, $visit->page_views);
        $this->assertSame('home', PageView::query()->sole()->page);
    }

    public function test_pages_in_one_sitting_are_one_visit(): void
    {
        $this->browse('/ar');
        $this->browse('/ar/menu');
        $this->browse('/ar/menu/sunrise-roast');

        $visit = Visit::query()->sole();
        $this->assertSame(3, $visit->page_views);
        $this->assertSame($this->roast->id, PageView::query()->where('page', 'item')->sole()->item_id);
    }

    public function test_a_returning_visitor_after_a_break_is_a_new_visit(): void
    {
        $this->browse('/ar');
        $visitorId = Visit::query()->sole()->visitor_id;
        $this->travel(45)->minutes();
        $this->withCookie(Tracker::COOKIE, $visitorId)->browse('/ar/menu');

        $this->assertSame(2, Visit::query()->count());
        $this->assertTrue(Visit::query()->latest('id')->first()->is_returning);
        $this->assertSame(1, Visit::query()->distinct()->count('visitor_id'));
    }

    public function test_bots_link_previews_prefetches_and_faten_are_not_counted(): void
    {
        $this->withHeaders(['User-Agent' => 'WhatsApp/2.24.1 A'])->get('/ar');
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)'])->get('/ar');
        $this->browse('/ar', ['Sec-Purpose' => 'prefetch']);
        $this->actingAs(User::factory()->create());
        $this->browse('/ar');

        $this->assertSame(0, Visit::query()->count());
    }

    public function test_the_source_comes_from_the_link_the_referrer_or_the_in_app_browser(): void
    {
        $this->browse('/ar?utm_source=ig&utm_campaign=ramadan');
        $this->assertSame(['instagram', 'ramadan'], [Visit::query()->latest('id')->first()->source, Visit::query()->latest('id')->first()->utm_campaign]);

        $this->flushSession();
        $this->app['cookie']->flushQueuedCookies();
        $this->withHeaders(['User-Agent' => self::INSTAGRAM])->withUnencryptedCookie(Tracker::COOKIE, 'x')->get('/ar');
        $this->assertSame('instagram', Visit::query()->latest('id')->first()->source);
    }

    public function test_source_detection(): void
    {
        $this->assertSame('instagram', Source::detect('https://l.instagram.com/?u=x', null, 'mofasando.store')['source']);
        $this->assertSame('google', Source::detect('https://www.google.com.eg/', null, 'mofasando.store')['source']);
        $this->assertSame('whatsapp', Source::detect(null, 'whatsapp', 'mofasando.store')['source']);
        $this->assertSame('direct', Source::detect('https://mofasando.store/ar/menu', null, 'mofasando.store')['source']);
        $this->assertSame('other', Source::detect('https://someblog.com/post', null, 'mofasando.store')['source']);
        $this->assertTrue(UserAgent::isBot('facebookexternalhit/1.1'));
        $this->assertSame('tablet', UserAgent::device('Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X)'));
    }

    public function test_added_to_the_bag_is_recorded_without_a_csrf_token(): void
    {
        $this->browse('/ar/menu/sunrise-roast');

        $this->withHeaders(['User-Agent' => self::PHONE])
            ->postJson('/t', ['name' => 'add_to_cart', 'item' => $this->roast->id, 'qty' => 2])
            ->assertNoContent();

        $event = VisitEvent::query()->sole();
        $this->assertSame([VisitEvent::ADD_TO_CART, $this->roast->id, 2], [$event->name, $event->item_id, $event->quantity]);
    }

    public function test_an_event_without_a_visit_or_with_a_made_up_name_is_ignored(): void
    {
        $this->postJson('/t', ['name' => 'add_to_cart', 'item' => $this->roast->id])->assertNoContent();
        $this->browse('/ar');
        $this->postJson('/t', ['name' => 'anything_else'])->assertNoContent();

        $this->assertSame(0, VisitEvent::query()->count());
    }

    public function test_the_visit_that_places_an_order_is_linked_to_it(): void
    {
        $this->browse('/ar/checkout');
        $visit = Visit::query()->sole();
        $order = Order::factory()->create();

        $request = Request::create('/livewire/update', 'POST', cookies: [Tracker::COOKIE => $visit->visitor_id]);
        $request->setLaravelSession($session = $this->app['session']->driver());
        $session->put('analytics.visit', $visit->id);
        app(Tracker::class)->orderPlaced($request, $order);

        $this->assertSame($order->id, $visit->fresh()->order_id);
        $this->assertTrue($order->fresh()->visit->is($visit));
        $this->assertSame(1, VisitEvent::query()->where('name', VisitEvent::ORDER_PLACED)->count());
    }

    public function test_the_report_counts_visitors_the_funnel_and_items(): void
    {
        $order = Order::factory()->create();
        $a = Visit::factory()->create(['visitor_id' => 'a', 'source' => 'instagram', 'order_id' => $order->id, 'page_views' => 4]);
        $b = Visit::factory()->create(['visitor_id' => 'b', 'source' => 'instagram', 'page_views' => 2]);
        Visit::factory()->create(['visitor_id' => 'a', 'source' => 'direct', 'page_views' => 1, 'is_returning' => true]);
        foreach ([$a, $b] as $visit) {
            PageView::query()->create(['visit_id' => $visit->id, 'page' => 'item', 'path' => '/ar/menu/sunrise-roast', 'item_id' => $this->roast->id, 'viewed_at' => now()]);
        }
        PageView::query()->create(['visit_id' => $a->id, 'page' => 'checkout', 'path' => '/ar/checkout', 'viewed_at' => now()]);
        VisitEvent::query()->create(['visit_id' => $a->id, 'name' => VisitEvent::ADD_TO_CART, 'item_id' => $this->roast->id, 'quantity' => 2, 'created_at' => now()]);

        $report = Report::for('7d');
        $totals = $report->totals();

        $this->assertSame([2, 3, 7, 1], [$totals['visitors'], $totals['visits'], $totals['page_views'], $totals['orders']]);
        $this->assertEqualsWithDelta(33.3, $totals['conversion'], 0.1);
        $this->assertSame(['visits' => 3, 'viewed_item' => 2, 'added' => 1, 'checkout' => 1, 'ordered' => 1], $report->funnel());
        $this->assertSame(['visits' => 2, 'orders' => 1], $report->sources()['instagram']);
        $this->assertSame([2, 2, 2], [$report->items()[0]['views'], $report->items()[0]['visitors'], $report->items()[0]['added']]);
        $this->assertCount(7, $report->series('day'));
        $series = $report->series('day');
        $this->assertSame(2, end($series)['visitors']);
    }

    public function test_faten_sees_the_visitors_page(): void
    {
        $this->actingAs(User::factory()->create());
        $visit = Visit::factory()->create(['page_views' => 1]);
        PageView::query()->create(['visit_id' => $visit->id, 'page' => 'item', 'path' => '/ar/menu/sunrise-roast', 'item_id' => $this->roast->id, 'viewed_at' => now()]);

        $this->get(Visitors::getUrl())->assertOk()->assertSee(__('Visitors'));

        foreach ([VisitorStats::class, VisitorsTrend::class, TrafficSources::class, VisitorFunnel::class, PopularItems::class, PagesAndDevices::class, BusyHours::class] as $widget) {
            Livewire::test($widget, ['pageFilters' => ['period' => '7d']])->assertOk();
        }
        Livewire::test(PopularItems::class, ['pageFilters' => ['period' => '7d']])->assertSee($this->roast->localized('name'));
        Livewire::test(RecentVisits::class, ['pageFilters' => ['period' => '7d']])
            ->assertCanSeeTableRecords([$visit]);
        $this->assertStringContainsString($this->roast->localized('name'), view('filament.visitors.journey', ['visit' => $visit])->render());
    }
}
