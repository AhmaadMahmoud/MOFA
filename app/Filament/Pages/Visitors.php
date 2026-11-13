<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Visitors\BusyHours;
use App\Filament\Widgets\Visitors\PagesAndDevices;
use App\Filament\Widgets\Visitors\PopularItems;
use App\Filament\Widgets\Visitors\RecentVisits;
use App\Filament\Widgets\Visitors\TrafficSources;
use App\Filament\Widgets\Visitors\VisitorFunnel;
use App\Filament\Widgets\Visitors\VisitorStats;
use App\Filament\Widgets\Visitors\VisitorsTrend;
use BackedEnum;
use Filament\Forms\Components\ToggleButtons;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Who visits the site and what they look at, from our own tracking (App\Analytics).
 * One period at the top drives every widget; each number is compared with the period before.
 */
class Visitors extends BaseDashboard
{
    use HasFiltersForm;

    protected static string $routePath = 'visitors';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('Visitors');
    }

    public function getTitle(): string
    {
        return __('Visitors');
    }

    public function getSubheading(): ?string
    {
        return __('Who visits the site, where they come from, and what they look at.');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            ToggleButtons::make('period')
                ->hiddenLabel()
                ->inline()
                ->default('30d')
                ->options([
                    '7d' => __('Last 7 days'),
                    '30d' => __('Last 30 days'),
                    '90d' => __('Last 3 months'),
                    'this_month' => __('This month'),
                    'last_month' => __('Last month'),
                ]),
        ])->columns(1);
    }

    public function getWidgets(): array
    {
        return [
            VisitorStats::class,
            VisitorsTrend::class,
            TrafficSources::class,
            VisitorFunnel::class,
            PopularItems::class,
            PagesAndDevices::class,
            BusyHours::class,
            RecentVisits::class,
        ];
    }

    public function getColumns(): int|array
    {
        return ['default' => 1, 'lg' => 2];
    }
}
