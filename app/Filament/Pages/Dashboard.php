<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Items\ItemResource;
use App\Filament\Widgets\ItemsNeedingAttention;
use App\Filament\Widgets\MenuOverview;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

/**
 * Faten's home in the dashboard: a greeting, the menu at a glance, and what to fix next.
 */
class Dashboard extends BaseDashboard
{
    public function getTitle(): string
    {
        $hour = (int) now('Africa/Cairo')->format('G');
        $greeting = match (true) {
            $hour < 12 => __('Good morning'),
            $hour < 17 => __('Good afternoon'),
            default => __('Good evening'),
        };
        $first = str(auth()->user()?->name)->before(' ')->toString();

        return filled($first) ? __(':greeting, :name', ['greeting' => $greeting, 'name' => $first]) : $greeting;
    }

    public static function getNavigationLabel(): string
    {
        return __('Home');
    }

    public function getSubheading(): ?string
    {
        return __("Here's your menu at a glance.");
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewSite')
                ->label(__('View the site'))
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn () => route('home', ['locale' => app()->getLocale() === 'ar' ? 'ar' : 'en']), shouldOpenInNewTab: true),
            Action::make('newItem')
                ->label(__('New item'))
                ->icon(Heroicon::OutlinedPlus)
                ->url(fn () => ItemResource::getUrl('create')),
        ];
    }

    public function getWidgets(): array
    {
        return [
            MenuOverview::class,
            ItemsNeedingAttention::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
