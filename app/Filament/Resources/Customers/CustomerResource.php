<?php

namespace App\Filament\Resources\Customers;

use App\Enums\OrderStatus;
use App\Filament\Resources\Customers\Pages\ManageCustomers;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Customer;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('Orders');
    }

    public static function getModelLabel(): string
    {
        return __('customer');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Customers');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label(__('Name'))->required()->maxLength(255),
            TextInput::make('email')->label(__('Email'))->email()->required()->maxLength(255)->extraInputAttributes(['dir' => 'ltr']),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        $paid = [OrderStatus::Confirmed, OrderStatus::OutForDelivery, OrderStatus::Delivered];

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withCount(['orders as paid_orders_count' => fn (Builder $o) => $o->whereIn('status', $paid)])
                ->withSum(['orders as paid_total' => fn (Builder $o) => $o->whereIn('status', $paid)], 'total')
                ->withMax('orders as last_order_at', 'created_at'))
            ->defaultSort('last_order_at', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('Name'))->searchable()->weight('semibold'),
                TextColumn::make('phone')->label(__('Phone'))->searchable()->copyable()->extraAttributes(['dir' => 'ltr']),
                TextColumn::make('email')->label(__('Email'))->searchable()->toggleable()->extraAttributes(['dir' => 'ltr']),
                TextColumn::make('paid_orders_count')->label(__('Paid orders'))->badge()->sortable(),
                TextColumn::make('paid_total')->label(__('Spent'))->formatStateUsing(fn ($state) => Money::format((int) $state))->sortable(),
                TextColumn::make('last_order_at')->label(__('Last order'))->since()->sortable(),
            ])
            ->recordActions([
                Action::make('orders')
                    ->label(__('Orders'))
                    ->icon(Heroicon::OutlinedShoppingBag)
                    ->url(fn (Customer $record) => OrderResource::getUrl('index', ['tab' => 'all', 'search' => $record->phone])),
                EditAction::make(),
            ])
            ->emptyStateHeading(__('No customers yet'))
            ->emptyStateDescription(__('Everyone who orders appears here, matched by phone number.'));
    }

    public static function getPages(): array
    {
        return ['index' => ManageCustomers::route('/')];
    }
}
