<?php

namespace App\Filament\Widgets\Visitors;

use Filament\Widgets\ChartWidget;

/** When people visit (Cairo time): a hint for when to post on Instagram. */
class BusyHours extends ChartWidget
{
    use ReadsPeriod;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '220px';

    public function getHeading(): ?string
    {
        return __('Busiest hours');
    }

    public function getDescription(): ?string
    {
        return __('When visits start, Cairo time. A good time to post on Instagram.');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $hours = $this->report()->hours();

        return [
            'datasets' => [[
                'label' => __('Visits'),
                'data' => array_values($hours),
                'backgroundColor' => '#e0c3a4',
                'hoverBackgroundColor' => '#b2563d',
                'borderRadius' => 4,
            ]],
            'labels' => array_map(fn (int $h) => now()->setTime($h, 0)->translatedFormat('g A'), array_keys($hours)),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}
