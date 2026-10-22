<?php

namespace Tests\Feature;

use App\Delivery\DeliveryDay;
use App\Delivery\DeliveryScheduler;
use App\Enums\OrderStatus;
use App\Models\ClosedDate;
use App\Models\DeliverySlot;
use App\Models\Order;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliverySchedulerTest extends TestCase
{
    use RefreshDatabase;

    private DeliverySlot $early;

    private DeliverySlot $late;

    protected function setUp(): void
    {
        parent::setUp();
        DeliverySlot::query()->delete(); // the migration's default slots
        $this->early = DeliverySlot::factory()->create(['label_en' => '8–10', 'starts_at' => '08:00', 'ends_at' => '10:00', 'sort_order' => 1]);
        $this->late = DeliverySlot::factory()->create(['label_en' => '10–12', 'starts_at' => '10:00', 'ends_at' => '12:00', 'sort_order' => 2]);
    }

    private function at(string $cairoTime): DeliveryScheduler
    {
        $now = CarbonImmutable::parse($cairoTime, 'Africa/Cairo');
        $this->travelTo($now);

        return new DeliveryScheduler($now);
    }

    private function dates(DeliveryScheduler $s): array
    {
        return $s->days()->map(fn (DeliveryDay $d) => $d->key())->all();
    }

    public function test_one_minute_before_the_cutoff_tomorrow_is_open(): void
    {
        $s = $this->at('2026-10-21 19:59');

        $this->assertSame('2026-10-22', $s->firstDate()->toDateString());
        $this->assertSame('2026-10-22', $this->dates($s)[0]);
    }

    public function test_at_and_after_the_cutoff_tomorrow_is_closed(): void
    {
        $this->assertSame('2026-10-23', $this->at('2026-10-21 20:00')->firstDate()->toDateString());
        $this->assertSame('2026-10-23', $this->at('2026-10-21 20:01')->firstDate()->toDateString());
    }

    public function test_just_after_midnight_the_next_morning_is_open(): void
    {
        $this->assertSame('2026-10-23', $this->at('2026-10-22 00:30')->firstDate()->toDateString());
    }

    public function test_the_cutoff_comes_from_settings(): void
    {
        Settings::put(['order_cutoff' => '18:30']);

        $this->assertSame('2026-10-22', $this->at('2026-10-21 18:29')->firstDate()->toDateString());
        $this->assertSame('2026-10-23', $this->at('2026-10-21 18:30')->firstDate()->toDateString());
    }

    public function test_the_window_runs_max_days_ahead_from_today(): void
    {
        Settings::put(['max_days_ahead' => 3]);

        $this->assertSame(['2026-10-22', '2026-10-23', '2026-10-24'], $this->dates($this->at('2026-10-21 09:00')));
        $this->assertSame(['2026-10-23', '2026-10-24'], $this->dates($this->at('2026-10-21 21:00')));
    }

    public function test_weekly_days_off_and_closed_dates_are_skipped(): void
    {
        // 2026-10-23 is a Friday (5); 2026-10-25 is closed for a holiday.
        Settings::put(['max_days_ahead' => 5, 'days_off' => [5]]);
        ClosedDate::query()->create(['date' => '2026-10-25', 'note' => 'Holiday']);

        $this->assertSame(['2026-10-22', '2026-10-24', '2026-10-26'], $this->dates($this->at('2026-10-21 09:00')));
    }

    public function test_a_closed_first_day_moves_to_the_next_open_one(): void
    {
        ClosedDate::query()->create(['date' => '2026-10-22']);

        $this->assertSame('2026-10-23', $this->dates($this->at('2026-10-21 09:00'))[0]);
    }

    public function test_a_full_slot_is_unavailable_while_the_other_stays_open(): void
    {
        $this->early->update(['capacity' => 2]);
        Order::factory()->count(2)->create(['delivery_date' => '2026-10-22', 'delivery_slot_id' => $this->early->id]);

        $s = $this->at('2026-10-21 09:00');
        $day = $s->day('2026-10-22');

        $this->assertFalse($day->slots[0]->available);
        $this->assertSame(0, $day->slots[0]->remaining);
        $this->assertTrue($day->slots[1]->available);
        $this->assertTrue($day->isAvailable());
        $this->assertFalse($s->isBookable('2026-10-22', $this->early->id));
        $this->assertTrue($s->isBookable('2026-10-22', $this->late->id));
        // Other days are untouched.
        $this->assertTrue($s->isBookable('2026-10-23', $this->early->id));
    }

    public function test_the_daily_capacity_fills_the_whole_day(): void
    {
        Settings::put(['daily_capacity' => 3]);
        Order::factory()->count(2)->create(['delivery_date' => '2026-10-22', 'delivery_slot_id' => $this->early->id]);
        Order::factory()->create(['delivery_date' => '2026-10-22', 'delivery_slot_id' => $this->late->id]);

        $s = $this->at('2026-10-21 09:00');

        $this->assertFalse($s->day('2026-10-22')->isAvailable());
        $this->assertTrue($s->day('2026-10-22')->dayFull);
        $this->assertTrue($s->day('2026-10-23')->isAvailable());
    }

    public function test_expired_and_cancelled_orders_free_their_place(): void
    {
        $this->early->update(['capacity' => 1]);
        $this->at('2026-10-21 09:00');
        $base = ['delivery_date' => '2026-10-22', 'delivery_slot_id' => $this->early->id];

        Order::factory()->status(OrderStatus::Expired)->create($base);
        Order::factory()->status(OrderStatus::Cancelled)->create($base);

        $this->assertTrue((new DeliveryScheduler(now()))->isBookable('2026-10-22', $this->early->id));
    }

    public function test_an_unpaid_order_holds_its_place_until_faten_confirms_or_cancels_it(): void
    {
        $this->early->update(['capacity' => 1]);
        $this->at('2026-10-21 09:00');
        $order = Order::factory()->status(OrderStatus::PendingPayment)->create([
            'delivery_date' => '2026-10-22', 'delivery_slot_id' => $this->early->id,
        ]);

        // Hours later it still holds the place: nothing cancels it automatically.
        $this->assertFalse((new DeliveryScheduler(now()->addHours(6)))->isBookable('2026-10-22', $this->early->id));

        $order->update(['status' => OrderStatus::Cancelled]);
        $this->assertTrue((new DeliveryScheduler(now()))->isBookable('2026-10-22', $this->early->id));
    }

    public function test_the_window_crosses_the_end_of_the_month_and_year(): void
    {
        Settings::put(['max_days_ahead' => 3]);

        $this->assertSame(['2026-11-01', '2026-11-02', '2026-11-03'], $this->dates($this->at('2026-10-31 07:00')));
        $this->assertSame(['2027-01-01', '2027-01-02', '2027-01-03'], $this->dates($this->at('2026-12-31 07:00')));
        $this->assertSame(['2027-01-02', '2027-01-03'], $this->dates($this->at('2026-12-31 22:00')));
    }

    public function test_inactive_slots_and_unknown_days_are_not_bookable(): void
    {
        $this->late->update(['is_active' => false]);
        $s = $this->at('2026-10-21 09:00');

        $this->assertCount(1, $s->day('2026-10-22')->slots);
        $this->assertFalse($s->isBookable('2026-10-22', $this->late->id));
        $this->assertFalse($s->isBookable('2026-10-21', $this->early->id), 'today');
        $this->assertFalse($s->isBookable('2026-11-30', $this->early->id), 'beyond the window');
    }
}
