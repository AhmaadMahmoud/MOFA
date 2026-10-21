<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Items\ItemResource;
use App\Models\Category;
use App\Models\Item;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MenuOverview extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $shown = Item::query()->active()->count();
        $hidden = Item::query()->count() - $shown;
        $needsAttention = ItemsNeedingAttention::query()->count();

        return [
            Stat::make(__('Items on the site'), $shown)
                ->description($hidden > 0 ? __(':n hidden', ['n' => $hidden]) : __('All items are shown'))
                ->icon(Heroicon::OutlinedCake)
                ->url(ItemResource::getUrl()),
            Stat::make(__('Categories'), Category::query()->active()->count())
                ->description(__('Shown on the menu'))
                ->icon(Heroicon::OutlinedSquares2x2)
                ->url(CategoryResource::getUrl()),
            Stat::make(__('Featured'), Item::query()->active()->where('is_featured', true)->count())
                ->description(__('On the home page'))
                ->icon(Heroicon::OutlinedStar),
            Stat::make(__('Need attention'), $needsAttention)
                ->description($needsAttention > 0 ? __('Missing a price, photo or details') : __('Everything is complete'))
                ->descriptionColor($needsAttention > 0 ? 'warning' : 'success')
                ->icon(Heroicon::OutlinedExclamationTriangle),
        ];
    }
}
