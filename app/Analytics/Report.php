<?php

namespace App\Analytics;

use App\Enums\OrderStatus;
use App\Models\Item;
use App\Models\OrderItem;
use App\Models\PageView;
use App\Models\Visit;
use App\Models\VisitEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * The numbers behind the Visitors page, for one period (Cairo time), with the period
 * before it for comparison. Grouping is done in PHP so it reads the same on MySQL and SQLite;
 * a small bakery's traffic is a few thousand rows.
 */
final class Report
{
    public const PERIODS = ['7d', '30d', '90d', 'this_month', 'last_month'];

    private ?Collection $visitsCache = null;

    public function __construct(
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
    ) {}

    public static function for(?string $period): self
    {
        $now = CarbonImmutable::now('Africa/Cairo');

        [$from, $to] = match ($period) {
            '7d' => [$now->subDays(6)->startOfDay(), $now->endOfDay()],
            '90d' => [$now->subDays(89)->startOfDay(), $now->endOfDay()],
            'this_month' => [$now->startOfMonth(), $now->endOfDay()],
            'last_month' => [$now->subMonthNoOverflow()->startOfMonth(), $now->subMonthNoOverflow()->endOfMonth()],
            default => [$now->subDays(29)->startOfDay(), $now->endOfDay()],
        };

        return new self($from, $to);
    }

    /** The same length of time just before this one. */
    public function previous(): self
    {
        $days = (int) $this->from->diffInDays($this->to->addSecond());

        return new self($this->from->subDays($days), $this->from->subSecond());
    }

    public function days(): int
    {
        return max(1, (int) round($this->from->diffInDays($this->to->addSecond())));
    }

    /** @return Collection<int, Visit> */
    public function visits(): Collection
    {
        return $this->visitsCache ??= Visit::query()
            ->whereBetween('started_at', [$this->from, $this->to])
            ->get(['id', 'visitor_id', 'started_at', 'source', 'device', 'browser', 'is_returning', 'page_views', 'order_id']);
    }

    /** Totals for the cards. */
    public function totals(): array
    {
        $visits = $this->visits();
        $count = $visits->count();
        $orders = $visits->whereNotNull('order_id')->count();
        $pageViews = (int) $visits->sum('page_views');

        return [
            'visitors' => $visits->pluck('visitor_id')->unique()->count(),
            'visits' => $count,
            'page_views' => $pageViews,
            'pages_per_visit' => $count ? round($pageViews / $count, 1) : 0.0,
            'orders' => $orders,
            'conversion' => $count ? round($orders / $count * 100, 1) : 0.0,
            'returning' => $visits->where('is_returning', true)->pluck('visitor_id')->unique()->count(),
        ];
    }

    /** Percentage change from the period before; null when there was nothing before. */
    public static function change(int|float $now, int|float $before): ?float
    {
        if ($before == 0) {
            return null;
        }

        return round(($now - $before) / $before * 100);
    }

    /**
     * Visitors and visits per day, week (Saturday to Friday) or month.
     *
     * @return array<string, array{label: string, visitors: int, visits: int}>
     */
    public function series(string $by = 'day'): array
    {
        $buckets = [];
        $cursor = $this->bucketStart($this->from, $by);
        while ($cursor->lte($this->to)) {
            $buckets[$cursor->format('Y-m-d')] = ['label' => $this->bucketLabel($cursor, $by), 'visitors' => [], 'visits' => 0];
            $cursor = match ($by) {
                'week' => $cursor->addWeek(),
                'month' => $cursor->addMonthNoOverflow(),
                default => $cursor->addDay(),
            };
        }

        foreach ($this->visits() as $visit) {
            $key = $this->bucketStart($visit->started_at->toImmutable()->setTimezone('Africa/Cairo'), $by)->format('Y-m-d');
            if (isset($buckets[$key])) {
                $buckets[$key]['visitors'][$visit->visitor_id] = true;
                $buckets[$key]['visits']++;
            }
        }

        return array_map(fn ($b) => ['label' => $b['label'], 'visitors' => count($b['visitors']), 'visits' => $b['visits']], $buckets);
    }

    /** @return array<string, array{visits: int, orders: int}> visits and orders per source, busiest first */
    public function sources(): array
    {
        return $this->visits()->groupBy('source')
            ->map(fn (Collection $group) => ['visits' => $group->count(), 'orders' => $group->whereNotNull('order_id')->count()])
            ->sortByDesc('visits')->all();
    }

    /** @return array<string, int> */
    public function devices(): array
    {
        return $this->visits()->countBy('device')->sortDesc()->all();
    }

    /** @return array<string, int> */
    public function browsers(): array
    {
        return $this->visits()->countBy(fn (Visit $v) => $v->browser ?? 'other')->sortDesc()->all();
    }

    /** @return array<int, int> visits that started in each hour of the day (Cairo time), 0–23 */
    public function hours(): array
    {
        $hours = array_fill(0, 24, 0);
        foreach ($this->visits() as $visit) {
            $hours[(int) $visit->started_at->copy()->setTimezone('Africa/Cairo')->format('G')]++;
        }

        return $hours;
    }

    /** @return array<string, array{views: int, visitors: int}> views per kind of page */
    public function pages(): array
    {
        $rows = PageView::query()
            ->join('visits', 'visits.id', '=', 'page_views.visit_id')
            ->whereBetween('page_views.viewed_at', [$this->from, $this->to])
            ->get(['page_views.page', 'visits.visitor_id']);

        return $rows->groupBy('page')
            ->map(fn (Collection $g) => ['views' => $g->count(), 'visitors' => $g->pluck('visitor_id')->unique()->count()])
            ->sortByDesc('views')->all();
    }

    /**
     * The items people look at: views, people, added to the bag, and pieces ordered in the period.
     *
     * @return list<array{item: Item, views: int, visitors: int, added: int, ordered: int}>
     */
    public function items(int $limit = 10): array
    {
        $views = PageView::query()
            ->join('visits', 'visits.id', '=', 'page_views.visit_id')
            ->where('page_views.page', 'item')->whereNotNull('page_views.item_id')
            ->whereBetween('page_views.viewed_at', [$this->from, $this->to])
            ->get(['page_views.item_id', 'visits.visitor_id'])
            ->groupBy('item_id');

        $added = VisitEvent::query()
            ->where('name', VisitEvent::ADD_TO_CART)->whereNotNull('item_id')
            ->whereBetween('created_at', [$this->from, $this->to])
            ->get(['item_id', 'quantity'])->groupBy('item_id')->map->sum('quantity');

        $ordered = OrderItem::query()
            ->whereHas('order', fn ($q) => $q->whereBetween('created_at', [$this->from, $this->to])
                ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Expired]))
            ->whereNotNull('item_id')
            ->get(['item_id', 'quantity'])->groupBy('item_id')->map->sum('quantity');

        $ids = $views->keys()->merge($added->keys())->merge($ordered->keys())->unique();
        $items = Item::query()->whereIn('id', $ids)->get()->keyBy('id');

        return $ids->filter(fn ($id) => $items->has($id))
            ->map(fn ($id) => [
                'item' => $items[$id],
                'views' => isset($views[$id]) ? $views[$id]->count() : 0,
                'visitors' => isset($views[$id]) ? $views[$id]->pluck('visitor_id')->unique()->count() : 0,
                'added' => (int) ($added[$id] ?? 0),
                'ordered' => (int) ($ordered[$id] ?? 0),
            ])
            ->sortByDesc(fn ($row) => [$row['views'], $row['added'], $row['ordered']])
            ->take($limit)->values()->all();
    }

    /**
     * How far visits get: in, looked at an item, added to the bag, opened checkout, ordered.
     *
     * @return array<string, int>
     */
    public function funnel(): array
    {
        $ids = $this->visits()->pluck('id');
        if ($ids->isEmpty()) {
            return ['visits' => 0, 'viewed_item' => 0, 'added' => 0, 'checkout' => 0, 'ordered' => 0];
        }

        $pages = PageView::query()->whereIn('visit_id', $ids)->whereIn('page', ['item', 'checkout'])
            ->get(['visit_id', 'page'])->groupBy('page')->map(fn ($g) => $g->pluck('visit_id')->unique()->count());
        $added = VisitEvent::query()->whereIn('visit_id', $ids)->where('name', VisitEvent::ADD_TO_CART)
            ->distinct()->count('visit_id');

        return [
            'visits' => $ids->count(),
            'viewed_item' => (int) ($pages['item'] ?? 0),
            'added' => $added,
            'checkout' => (int) ($pages['checkout'] ?? 0),
            'ordered' => $this->visits()->whereNotNull('order_id')->count(),
        ];
    }

    private function bucketStart(CarbonImmutable $at, string $by): CarbonImmutable
    {
        return match ($by) {
            'week' => $at->startOfWeek(CarbonImmutable::SATURDAY),
            'month' => $at->startOfMonth(),
            default => $at->startOfDay(),
        };
    }

    private function bucketLabel(CarbonImmutable $at, string $by): string
    {
        return match ($by) {
            'week' => $at->translatedFormat('j M'),
            'month' => $at->translatedFormat('M Y'),
            default => $at->translatedFormat('j M'),
        };
    }
}
