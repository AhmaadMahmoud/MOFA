<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Orders whose InstaPay receipt should be on WhatsApp by now: Faten checks and confirms. */
class PaymentsToConfirm extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Order::query()->awaitingConfirmation()->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Payments to confirm'))
            ->description(__('Customers send the InstaPay receipt on WhatsApp. Check it and confirm the order, or cancel it if the payment never comes.'))
            ->query(Order::query()->awaitingConfirmation()->with('customer')->oldest())
            ->paginated(false)
            ->columns([
                TextColumn::make('number')->label(__('Order'))->weight('semibold')->extraAttributes(['dir' => 'ltr']),
                TextColumn::make('customer.name')->label(__('Customer'))->description(fn (Order $record) => $record->customer->phone),
                TextColumn::make('total')->label(__('Total'))->formatStateUsing(fn (int $state) => Money::format($state)),
                TextColumn::make('delivery_date')->label(__('Delivery'))->date('D j M'),
                TextColumn::make('created_at')->label(__('Placed'))->since(),
            ])
            ->recordUrl(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                Action::make('open')
                    ->label(__('Open'))
                    ->url(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
