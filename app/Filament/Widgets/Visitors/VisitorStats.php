<?php

namespace App\Filament\Widgets\Visitors;

use App\Analytics\Report;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** The four headline numbers, each compared with the period before. */
class VisitorStats extends StatsOverviewWidget
{
    use ReadsPeriod;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $report = $this->report();
        $now = $report->totals();
        $before = $report->previous()->totals();
        $trend = array_column($report->series('day'), 'visitors');

        return [
            $this->stat(__('Visitors'), $now['visitors'], $before['visitors'], Heroicon::OutlinedUsers)
                ->chart(array_values($trend))
                ->description($this->changeText($now['visitors'], $before['visitors'])
                    .' · '.trans_choice(':count returning|:count returning', $now['returning'])),
            $this->stat(__('Visits'), $now['visits'], $before['visits'], Heroicon::OutlinedCursorArrowRays),
            $this->stat(__('Pages viewed'), $now['page_views'], $before['page_views'], Heroicon::OutlinedDocumentText)
                ->description(__(':count pages per visit', ['count' => $now['pages_per_visit']])),
            $this->stat(__('Orders from the site'), $now['orders'], $before['orders'], Heroicon::OutlinedShoppingBag)
                ->description(__(':rate% of visits ordered', ['rate' => $now['conversion']])),
        ];
    }

    private function stat(string $label, int $now, int $before, Heroicon $icon): Stat
    {
        $change = Report::change($now, $before);

        return Stat::make($label, number_format($now))
            ->icon($icon)
            ->description($this->changeText($now, $before))
            ->descriptionIcon($change === null ? null : ($change >= 0 ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown))
            ->color($change === null || $change == 0 ? 'gray' : ($change > 0 ? 'success' : 'danger'));
    }

    private function changeText(int $now, int $before): string
    {
        $change = Report::change($now, $before);

        return match (true) {
            $change === null => __('No data before to compare'),
            $change > 0 => __('Up :pct% from the period before', ['pct' => $change]),
            $change < 0 => __('Down :pct% from the period before', ['pct' => abs($change)]),
            default => __('Same as the period before'),
        };
    }
}
