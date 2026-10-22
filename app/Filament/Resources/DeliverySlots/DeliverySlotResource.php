<?php

namespace App\Filament\Resources\DeliverySlots;

use App\Filament\Resources\DeliverySlots\Pages\ManageDeliverySlots;
use App\Models\DeliverySlot;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class DeliverySlotResource extends Resource
{
    protected static ?string $model = DeliverySlot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('Delivery');
    }

    public static function getModelLabel(): string
    {
        return __('time slot');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Time slots');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label_en')->label(__('Label (English)'))->placeholder('8–10 am')->required()->maxLength(60)->extraInputAttributes(['dir' => 'ltr']),
            TextInput::make('label_ar')->label(__('Label (Arabic)'))->placeholder('8–10 صباحًا')->required()->maxLength(60)->extraInputAttributes(['dir' => 'rtl']),
            Grid::make(2)->schema([
                TimePicker::make('starts_at')->label(__('From'))->seconds(false)->required(),
                TimePicker::make('ends_at')->label(__('To'))->seconds(false)->required()->after('starts_at'),
            ]),
            TextInput::make('capacity')
                ->label(__('Orders per day in this slot'))
                ->helperText(__('Leave empty for no limit.'))
                ->numeric()
                ->integer()
                ->minValue(1),
            Toggle::make('is_active')->label(__('Shown at checkout'))->default(true),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('label_en')->label(__('Label (English)'))->weight('semibold'),
                TextColumn::make('label_ar')->label(__('Label (Arabic)')),
                TextColumn::make('capacity')->label(__('Orders per day'))->placeholder(__('No limit')),
                ToggleColumn::make('is_active')->label(__('Shown')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (DeliverySlot $record) => $record->orders()->exists()),
            ])
            ->emptyStateHeading(__('No time slots yet'))
            ->emptyStateDescription(__('Add the delivery windows customers can choose, like 8–10 am.'));
    }

    public static function getPages(): array
    {
        return ['index' => ManageDeliverySlots::route('/')];
    }
}
