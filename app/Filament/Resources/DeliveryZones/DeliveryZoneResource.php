<?php

namespace App\Filament\Resources\DeliveryZones;

use App\Filament\Resources\DeliveryZones\Pages\ManageDeliveryZones;
use App\Models\DeliveryZone;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class DeliveryZoneResource extends Resource
{
    protected static ?string $model = DeliveryZone::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('Delivery');
    }

    public static function getModelLabel(): string
    {
        return __('area');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Delivery areas');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name_en')->label(__('Name (English)'))->required()->maxLength(255)->extraInputAttributes(['dir' => 'ltr']),
            TextInput::make('name_ar')->label(__('Name (Arabic)'))->required()->maxLength(255)->extraInputAttributes(['dir' => 'rtl']),
            TextInput::make('fee')
                ->label(__('Delivery fee'))
                ->numeric()
                ->minValue(0)
                ->step(0.5)
                ->suffix(__('EGP'))
                ->default(0)
                ->required()
                ->formatStateUsing(fn ($state) => Money::toPounds($state === null ? null : (int) $state))
                ->dehydrateStateUsing(fn ($state) => Money::toPiasters($state) ?? 0),
            Toggle::make('is_pickup')
                ->label(__('Pickup from the bakery'))
                ->helperText(__('Customers choosing this don\'t enter an address.')),
            Toggle::make('is_active')->label(__('Shown at checkout'))->default(true),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name_en')->label(__('Name (English)'))->searchable()->weight('semibold'),
                TextColumn::make('name_ar')->label(__('Name (Arabic)'))->searchable(),
                TextColumn::make('fee')->label(__('Delivery fee'))->formatStateUsing(fn (int $state) => $state > 0 ? Money::format($state) : __('Free')),
                IconColumn::make('is_pickup')->label(__('Pickup'))->boolean()->trueIcon(Heroicon::OutlinedBuildingStorefront)->falseIcon(null),
                ToggleColumn::make('is_active')->label(__('Shown')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (DeliveryZone $record) => $record->orders()->exists()),
            ])
            ->emptyStateHeading(__('No delivery areas yet'))
            ->emptyStateDescription(__('Add the areas you deliver to, each with its fee. Add "Pickup" with no fee if customers can collect.'));
    }

    public static function getPages(): array
    {
        return ['index' => ManageDeliveryZones::route('/')];
    }
}
