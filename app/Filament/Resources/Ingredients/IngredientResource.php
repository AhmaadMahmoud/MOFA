<?php

namespace App\Filament\Resources\Ingredients;

use App\Filament\Resources\Ingredients\Pages\ManageIngredients;
use App\Models\Ingredient;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use App\Support\Costing;
use App\Support\Money;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IngredientResource extends Resource
{
    protected static ?string $model = Ingredient::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('Menu');
    }

    public static function getModelLabel(): string
    {
        return __('ingredient');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Ingredients');
    }

    /** Shared with the inline "new ingredient" form on the item screen. */
    public static function formFields(): array
    {
        return [
            TextInput::make('name_en')->label(__('Name (English)'))->required()->maxLength(255)->extraInputAttributes(['dir' => 'ltr'])->unique(Ingredient::class, 'name_en', ignoreRecord: true),
            TextInput::make('name_ar')->label(__('Name (Arabic)'))->required()->maxLength(255)->extraInputAttributes(['dir' => 'rtl']),
            // What it costs to buy, e.g. 300 g for 150 EGP. Feeds the item cost (App\Support\Costing).
            Fieldset::make(__('Purchase price'))
                ->columns(3)
                ->schema([
                    TextInput::make('purchase_quantity')->label(__('Quantity bought'))->numeric()->minValue(0)->requiredWith('purchase_price'),
                    Select::make('purchase_unit')->label(__('Unit'))
                        ->options(collect(array_keys(Costing::PURCHASE_UNITS))->mapWithKeys(fn ($u) => [$u => __($u)]))
                        ->requiredWith('purchase_quantity'),
                    TextInput::make('purchase_price')->label(__('Price paid'))->numeric()->minValue(0)->step(0.5)->suffix('EGP')
                        ->requiredWith('purchase_quantity')
                        ->formatStateUsing(fn (?int $state) => Money::toPounds($state))
                        ->dehydrateStateUsing(fn ($state) => Money::toPiasters($state)),
                ]),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(static::formFields())->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name_en')
            ->columns([
                TextColumn::make('name_en')->label(__('Name (English)'))->searchable()->sortable()->weight('semibold'),
                TextColumn::make('name_ar')->label(__('Name (Arabic)'))->searchable(),
                TextColumn::make('purchase_price')->label(__('Purchase price'))
                    ->state(fn (Ingredient $record) => $record->purchaseLabel())
                    ->placeholder(__('Not priced'))
                    ->color(fn (Ingredient $record) => $record->purchaseLabel() ? null : 'warning'),
                TextColumn::make('unit_cost')->label(__('Cost per 100 g / 100 ml / piece'))
                    ->state(function (Ingredient $record) {
                        [$base] = Costing::purchaseBase($record) ?? [null];
                        if ($base === null) {
                            return null;
                        }
                        $per = $base === 'pc' ? 1 : 100;

                        return Costing::money(Costing::unitCost($record) * $per).' / '.$per.' '.__($base);
                    })
                    ->placeholder('—'),
                TextColumn::make('items_count')->label(__('Used in'))->counts('items')->badge(),
            ])
            ->recordActions([
                EditAction::make()->iconButton()->tooltip(__('Edit')),
                DeleteAction::make()->iconButton()
                    ->disabled(fn (Ingredient $record) => $record->items()->exists())
                    ->tooltip(fn (Ingredient $record) => $record->items()->exists() ? __('Remove it from its items first') : __('Delete')),
            ])
            ->emptyStateHeading(__('No ingredients yet'))
            ->emptyStateDescription(__('Ingredients can also be added right from an item.'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageIngredients::route('/'),
        ];
    }
}
