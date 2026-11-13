<?php

namespace App\Filament\Widgets\Visitors;

use App\Analytics\Labels;
use Filament\Widgets\Widget;

/** Which pages are seen most, and on what devices and browsers. */
class PagesAndDevices extends Widget
{
    use ReadsPeriod;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.visitors.pages-devices';

    protected function getViewData(): array
    {
        $report = $this->report();
        $bars = function (array $counts, callable $label) {
            $total = max(1, array_sum($counts));

            return collect($counts)->map(fn ($count, $key) => [
                'label' => $label($key), 'count' => $count, 'pct' => round($count / $total * 100),
            ])->values()->all();
        };

        return [
            'pages' => collect($report->pages())->map(fn ($row, $page) => [
                'label' => Labels::page($page), 'count' => $row['views'], 'visitors' => $row['visitors'],
            ])->values()->all(),
            'devices' => $bars($report->devices(), fn ($d) => Labels::device($d)),
            'browsers' => $bars(array_slice($report->browsers(), 0, 5, true), fn ($b) => $b === 'other' ? __('Other') : $b),
        ];
    }
}
