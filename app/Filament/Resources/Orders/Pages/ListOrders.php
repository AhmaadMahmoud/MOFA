<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    public function getTabs(): array
    {
        $waiting = Order::query()->awaitingConfirmation()->count();

        return [
            'upcoming' => Tab::make(__('Upcoming'))
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereIn('status', [OrderStatus::Confirmed, OrderStatus::OutForDelivery])
                    ->whereDate('delivery_date', '>=', today())
                    ->reorder('delivery_date')->orderBy('delivery_slot_id')),
            // The receipt arrives on WhatsApp; these wait for Faten to confirm the payment.
            'unpaid' => Tab::make(__('To confirm'))
                ->badge($waiting ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->awaitingConfirmation()->reorder('created_at')),
            'all' => Tab::make(__('All')),
        ];
    }

    /** Open on the payments to confirm when there are some. */
    public function getDefaultActiveTab(): string|int|null
    {
        return Order::query()->awaitingConfirmation()->exists() ? 'unpaid' : 'upcoming';
    }
}
