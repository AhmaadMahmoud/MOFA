<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Ingredients\IngredientResource;
use App\Models\Ingredient;
use App\Support\Stock;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Ingredients at or under their warning level, or out. Only shows once Faten tracks some stock.
 * A warning only: items stay on the menu, Faten decides what to do.
 */
class LowStock extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Ingredient::query()->whereNotNull('stock_quantity')->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Running low'))
            ->description(__('Ingredients to buy soon. Confirmed orders take from the stock.'))
            ->query(Ingredient::query()->lowOnStock()->orderBy('stock_quantity'))
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('name_en')->label(__('Ingredient'))->weight('semibold')
                    ->state(fn (Ingredient $record) => $record->localized('name')),
                TextColumn::make('stock_quantity')->label(__('In stock'))
                    ->state(fn (Ingredient $record) => Stock::label($record->stock_quantity, $record->stock_unit))
                    ->badge()
                    ->color(fn (Ingredient $record) => (float) $record->stock_quantity <= 0 ? 'danger' : 'warning'),
                TextColumn::make('low_stock_at')->label(__('Warn me at'))
                    ->state(fn (Ingredient $record) => $record->low_stock_at !== null ? Stock::label($record->low_stock_at, $record->stock_unit) : null)
                    ->placeholder('—'),
            ])
            ->recordUrl(fn () => IngredientResource::getUrl('index', ['tableFilters' => ['low' => ['isActive' => true]]]))
            ->emptyStateHeading(__('Everything is stocked'))
            ->emptyStateDescription(__('No ingredient is running low.'))
            ->emptyStateIcon('heroicon-o-check-badge');
    }
}
