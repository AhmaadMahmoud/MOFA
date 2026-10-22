<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Pages\PrepList;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Money;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrdersOverview extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = '60s';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $tomorrow = today()->addDay();
        $toBake = array_map(fn ($s) => $s->value, OrderStatus::toBake());

        $tomorrowOrders = Order::query()->forDate($tomorrow)->whereIn('status', $toBake)->count();
        $tomorrowPieces = (int) OrderItem::query()
            ->whereHas('order', fn ($query) => $query->whereDate('delivery_date', $tomorrow)->whereIn('status', $toBake))
            ->sum('quantity');
        $toConfirm = Order::query()->awaitingConfirmation()->count();
        $upcoming = Order::query()->whereIn('status', [OrderStatus::Confirmed])->whereBetween('delivery_date', [today()->toDateString(), today()->addDays(7)->toDateString().' 23:59:59'])->count();
        $week = (int) Order::query()
            ->whereIn('status', $toBake)
            ->whereBetween('delivery_date', [today()->startOfWeek(6)->toDateString(), today()->startOfWeek(6)->addDays(6)->toDateString().' 23:59:59'])
            ->sum('total');

        return [
            Stat::make(__('Orders for tomorrow'), $tomorrowOrders)
                ->description(trans_choice(':count piece to bake|:count pieces to bake', $tomorrowPieces))
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->url(PrepList::getUrl()),
            Stat::make(__('Payments to confirm'), $toConfirm)
                ->description($toConfirm > 0 ? __('Check the receipts on WhatsApp, then confirm') : __('All caught up'))
                ->color($toConfirm > 0 ? 'warning' : null)
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->url(OrderResource::getUrl('index', ['tab' => 'unpaid'])),
            Stat::make(__('Confirmed this week'), $upcoming)
                ->description(__('Orders for the next 7 days'))
                ->icon(Heroicon::OutlinedCalendarDays)
                ->url(OrderResource::getUrl('index', ['tab' => 'upcoming'])),
            Stat::make(__('Sales this week'), Money::format($week))
                ->description(__('Confirmed orders, Saturday to Friday'))
                ->icon(Heroicon::OutlinedBanknotes),
        ];
    }
}
