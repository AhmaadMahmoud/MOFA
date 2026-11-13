<?php

namespace App\Filament\Widgets\Visitors;

use App\Analytics\Labels;
use Filament\Widgets\Widget;

/** Where visits come from, and how many of them ordered. */
class TrafficSources extends Widget
{
    use ReadsPeriod;

    protected static bool $isDiscovered = false;

    protected string $view = 'filament.visitors.sources';

    protected function getViewData(): array
    {
        $sources = $this->report()->sources();
        $top = max([1, ...array_column($sources, 'visits')]);

        return ['rows' => collect($sources)->map(fn ($row, $source) => [
            'label' => Labels::source($source),
            'visits' => $row['visits'],
            'orders' => $row['orders'],
            'pct' => round($row['visits'] / $top * 100),
        ])->values()->all()];
    }
}
