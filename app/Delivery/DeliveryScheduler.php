<?php

namespace App\Delivery;

use App\Models\ClosedDate;
use App\Models\DeliverySlot;
use App\Models\Order;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Which mornings a customer can book, in Cairo time.
 *
 * - Before the cutoff (default 8pm) the first day is tomorrow; from the cutoff on, the day after.
 * - Days run up to "max days ahead" from today.
 * - Weekly days off and closed dates are skipped.
 * - A slot is full when its orders reach its capacity; a day is full at the daily capacity.
 *   Unpaid orders hold their place until Faten confirms or cancels them.
 */
final class DeliveryScheduler
{
    public const TIMEZONE = 'Africa/Cairo';

    private CarbonImmutable $now;

    public function __construct(?CarbonInterface $now = null)
    {
        $this->now = CarbonImmutable::instance($now ?? now())->setTimezone(self::TIMEZONE);
    }

    public function now(): CarbonImmutable
    {
        return $this->now;
    }

    public function cutoffToday(): CarbonImmutable
    {
        [$h, $m] = array_map('intval', explode(':', Settings::cutoff()) + [1 => 0]);

        return $this->now->startOfDay()->setTime($h, $m);
    }

    public function firstDate(): CarbonImmutable
    {
        return $this->now->startOfDay()->addDays($this->now->lessThan($this->cutoffToday()) ? 1 : 2);
    }

    public function lastDate(): CarbonImmutable
    {
        return $this->now->startOfDay()->addDays(Settings::maxDaysAhead());
    }

    /**
     * Every open day in the booking window, full ones included (shown as "fully booked").
     *
     * @return Collection<int, DeliveryDay>
     */
    public function days(): Collection
    {
        $first = $this->firstDate();
        $last = $this->lastDate();
        if ($first->greaterThan($last)) {
            return collect();
        }

        $slots = DeliverySlot::query()->active()->ordered()->get();
        $closed = ClosedDate::query()
            ->whereBetween('date', [$first->toDateString(), $last->toDateString().' 23:59:59'])
            ->pluck('date')
            ->map(fn ($d) => CarbonImmutable::parse($d)->toDateString())
            ->flip();
        $daysOff = array_flip(Settings::daysOff());
        $counts = $this->bookedCounts($first, $last);
        $dailyCapacity = Settings::dailyCapacity();

        $days = collect();
        for ($date = $first; $date->lessThanOrEqualTo($last); $date = $date->addDay()) {
            $key = $date->toDateString();
            if (isset($daysOff[$date->dayOfWeek]) || isset($closed[$key])) {
                continue;
            }

            $dayTotal = array_sum($counts[$key] ?? []);
            $dayFull = $dailyCapacity !== null && $dayTotal >= $dailyCapacity;

            $options = $slots->map(function (DeliverySlot $slot) use ($counts, $key, $dayFull) {
                $taken = $counts[$key][$slot->id] ?? 0;
                $remaining = $slot->capacity === null ? null : max(0, $slot->capacity - $taken);

                return new SlotOption($slot, $remaining, ! $dayFull && ($remaining === null || $remaining > 0));
            })->values()->all();

            $days->push(new DeliveryDay($date, $options, $dayFull));
        }

        return $days;
    }

    public function day(string $date): ?DeliveryDay
    {
        return $this->days()->first(fn (DeliveryDay $d) => $d->key() === $date);
    }

    /** Whether an order can still be placed for this day and slot, right now. */
    public function isBookable(string $date, int $slotId): bool
    {
        $day = $this->day($date);
        if (! $day) {
            return false;
        }

        foreach ($day->slots as $option) {
            if ($option->slot->id === $slotId) {
                return $option->available;
            }
        }

        return false;
    }

    /** @return array<string, array<int, int>> date => slot id => orders */
    private function bookedCounts(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = Order::query()
            ->holdingCapacity()
            // Inclusive of the whole last day, whether the driver stores "Y-m-d" or "Y-m-d 00:00:00".
            ->whereBetween('delivery_date', [$from->toDateString(), $to->toDateString().' 23:59:59'])
            ->selectRaw('delivery_date, delivery_slot_id, count(*) as aggregate')
            ->groupBy('delivery_date', 'delivery_slot_id')
            ->get();

        $counts = [];
        foreach ($rows as $row) {
            $key = CarbonImmutable::parse($row->getRawOriginal('delivery_date'))->toDateString();
            $counts[$key][(int) $row->delivery_slot_id] = (int) $row->aggregate;
        }

        return $counts;
    }
}
