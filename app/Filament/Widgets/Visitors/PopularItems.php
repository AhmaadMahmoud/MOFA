<?php

namespace App\Filament\Widgets\Visitors;

use Filament\Widgets\Widget;

/** The items people look at most, and how often a look becomes a bag and an order. */
class PopularItems extends Widget
{
    use ReadsPeriod;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.visitors.items';

    protected function getViewData(): array
    {
        $rows = $this->report()->items(10);
        $top = max([1, ...array_column($rows, 'views')]);

        return ['rows' => array_map(fn ($row) => $row + ['pct' => round($row['views'] / $top * 100)], $rows)];
    }
}
