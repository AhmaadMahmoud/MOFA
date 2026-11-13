<?php

namespace App\Filament\Resources\Ingredients;

use App\Filament\Resources\Ingredients\Pages\ManageIngredients;
use App\Models\Ingredient;
use BackedEnum;
use App\Support\Costing;
use App\Support\Money;
use App\Support\Stock;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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

    /** Units stock can be counted in (same as purchases: g, kg, ml, l, piece). */
    private static function unitOptions(): array
    {
        return collect(array_keys(Costing::PURCHASE_UNITS))->mapWithKeys(fn ($u) => [$u => __($u)])->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            ...static::formFields(),
            // On hand now. A confirmed order takes its ingredients out (App\Support\Stock). Leave empty to not track it.
            Fieldset::make(__('Stock'))
                ->columns(3)
                ->schema([
                    TextInput::make('stock_quantity')->label(__('In stock now'))->numeric()->step(0.001)
                        ->helperText(__('Leave empty to not track this ingredient.')),
                    Select::make('stock_unit')->label(__('Unit'))->options(static::unitOptions())
                        ->requiredWith('stock_quantity'),
                    TextInput::make('low_stock_at')->label(__('Warn me at'))->numeric()->minValue(0)->step(0.001)
                        ->helperText(__('In the same unit. Empty: warn only when it runs out.')),
                ]),
        ])->columns(1);
    }

    /** Running low, on the sidebar. */
    public static function getNavigationBadge(): ?string
    {
        $count = Ingredient::query()->lowOnStock()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('Running low');
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
                TextColumn::make('stock_quantity')->label(__('In stock'))
                    ->state(fn (Ingredient $record) => Stock::tracked($record) ? Stock::label($record->stock_quantity, $record->stock_unit) : null)
                    ->placeholder(__('Not tracked'))
                    ->badge(fn (Ingredient $record) => Stock::tracked($record))
                    ->color(fn (Ingredient $record) => match (true) {
                        ! Stock::tracked($record) => null,
                        (float) $record->stock_quantity <= 0 => 'danger',
                        Stock::isLow($record) => 'warning',
                        default => 'success',
                    })
                    ->sortable(),
                TextColumn::make('items_count')->label(__('Used in'))->counts('items')->badge(),
            ])
            ->filters([
                Filter::make('low')->label(__('Running low'))->query(fn (Builder $query) => $query->lowOnStock()),
            ])
            ->recordActions([
                // Bought more: add to what's there instead of retyping the total.
                Action::make('restock')
                    ->label(__('Add stock'))
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->iconButton()
                    ->tooltip(__('Add stock'))
                    ->visible(fn (Ingredient $record) => Stock::tracked($record))
                    ->schema(fn (Ingredient $record) => [
                        TextInput::make('amount')->label(__('Amount bought'))->numeric()->required()->minValue(0.001)->step(0.001)
                            ->suffix(__($record->stock_unit)),
                    ])
                    ->modalHeading(fn (Ingredient $record) => __('Add stock: :name', ['name' => $record->localized('name')]))
                    ->modalSubmitActionLabel(__('Add'))
                    ->action(function (Ingredient $record, array $data) {
                        $record->update(['stock_quantity' => round((float) $record->stock_quantity + (float) $data['amount'], 3)]);
                        Notification::make()->success()
                            ->title(__('Now :left in stock', ['left' => Stock::label($record->stock_quantity, $record->stock_unit)]))
                            ->send();
                    }),
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
