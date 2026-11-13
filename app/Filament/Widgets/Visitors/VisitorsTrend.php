<?php

namespace App\Filament\Widgets\Visitors;

use Filament\Widgets\ChartWidget;

/** Visitors and visits over the period, by day, week or month. */
class VisitorsTrend extends ChartWidget
{
    use ReadsPeriod;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    public ?string $filter = 'day';

    public function getHeading(): ?string
    {
        return __('Visitors over time');
    }

    protected function getFilters(): ?array
    {
        return ['day' => __('Daily'), 'week' => __('Weekly'), 'month' => __('Monthly')];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $series = $this->report()->series($this->filter ?? 'day');

        return [
            'datasets' => [
                [
                    'label' => __('Visitors'),
                    'data' => array_values(array_column($series, 'visitors')),
                    'borderColor' => '#b2563d',
                    'backgroundColor' => 'rgba(178, 86, 61, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
                [
                    'label' => __('Visits'),
                    'data' => array_values(array_column($series, 'visits')),
                    'borderColor' => '#786b49',
                    'backgroundColor' => 'transparent',
                    'borderDash' => [5, 4],
                    'tension' => 0.35,
                ],
            ],
            'labels' => array_values(array_column($series, 'label')),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => true, 'position' => 'bottom']],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}
