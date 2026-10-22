<?php

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'number';

    public static function getNavigationGroup(): ?string
    {
        return __('Orders');
    }

    public static function getModelLabel(): string
    {
        return __('order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Orders');
    }

    /** Receipts waiting for Faten, on the sidebar. */
    public static function getNavigationBadge(): ?string
    {
        $count = Order::query()->awaitingConfirmation()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('Payments to confirm');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['number', 'customer.name', 'customer.phone'];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['customer', 'payment']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('number')
                    ->label(__('Order'))
                    ->searchable()
                    ->copyable()
                    ->weight('semibold')
                    ->extraAttributes(['dir' => 'ltr'])
                    ->description(fn (Order $record) => $record->created_at->diffForHumans()),
                TextColumn::make('customer.name')
                    ->label(__('Customer'))
                    ->searchable(['name', 'phone'])
                    ->description(fn (Order $record) => $record->customer->phone),
                TextColumn::make('delivery_date')
                    ->label(__('Delivery'))
                    ->date('D j M')
                    ->sortable()
                    ->description(fn (Order $record) => $record->slotLabel().' · '.$record->zoneName()),
                TextColumn::make('total')
                    ->label(__('Total'))
                    ->formatStateUsing(fn (int $state) => Money::format($state))
                    ->sortable(),
                TextColumn::make('status')->label(__('Status'))->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('Status'))->options(OrderStatus::class)->multiple(),
                Filter::make('delivery_date')
                    ->schema([DatePicker::make('on')->label(__('Delivery day'))->native(false)])
                    ->query(fn (Builder $query, array $data) => $query->when($data['on'] ?? null, fn (Builder $query, $on) => $query->whereDate('delivery_date', $on)))
                    ->indicateUsing(fn (array $data) => ($data['on'] ?? null) ? __('Delivery day').': '.\Illuminate\Support\Carbon::parse($data['on'])->translatedFormat('D j M') : null),
            ])
            ->recordActions([ViewAction::make()])
            ->recordUrl(fn (Order $record) => static::getUrl('view', ['record' => $record]))
            ->emptyStateIcon(Heroicon::OutlinedShoppingBag)
            ->emptyStateHeading(__('No orders here'))
            ->emptyStateDescription(__('New orders appear here as soon as customers place them.'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
