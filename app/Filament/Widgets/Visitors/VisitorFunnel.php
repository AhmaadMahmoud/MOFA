<?php

namespace App\Filament\Widgets\Visitors;

use Filament\Widgets\Widget;

/** How far visits get, from coming in to ordering. */
class VisitorFunnel extends Widget
{
    use ReadsPeriod;

    protected static bool $isDiscovered = false;

    protected string $view = 'filament.visitors.funnel';

    protected function getViewData(): array
    {
        $f = $this->report()->funnel();
        $top = max(1, $f['visits']);

        $steps = [
            [__('Visited the site'), $f['visits']],
            [__('Opened an item'), $f['viewed_item']],
            [__('Added to the bag'), $f['added']],
            [__('Opened checkout'), $f['checkout']],
            [__('Placed an order'), $f['ordered']],
        ];

        return ['steps' => array_map(fn ($s) => ['label' => $s[0], 'count' => $s[1], 'pct' => round($s[1] / $top * 100)], $steps)];
    }
}
