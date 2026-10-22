<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Items\ItemResource;
use App\Models\Item;
use Filament\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Items that would look incomplete on the site: no price, no photo, no nutrition,
 * or a description missing in one of the two languages.
 */
class ItemsNeedingAttention extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public static function query(): Builder
    {
        return Item::query()->where(fn (Builder $query) => $query
            ->where('price', 0)
            ->orWhereNull('images')
            ->orWhereJsonLength('images', 0)
            ->orWhereNull('calories')
            ->orWhereNull('description_en')->orWhere('description_en', '')
            ->orWhereNull('description_ar')->orWhere('description_ar', ''));
    }

    /** @return list<string> */
    public static function issues(Item $item): array
    {
        return array_values(array_filter([
            $item->price === 0 ? __('No price') : null,
            empty($item->images) ? __('No photo') : null,
            $item->calories === null ? __('No nutrition') : null,
            blank($item->description_en) ? __('No English description') : null,
            blank($item->description_ar) ? __('No Arabic description') : null,
        ]));
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Items to finish'))
            ->description(__('These items show on the site but are missing something.'))
            ->query(static::query()->with('category')->orderBy('sort_order'))
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->columns([
                ImageColumn::make('images')->label('')->disk('public')->limit(1)->square()->imageSize(44),
                TextColumn::make('name_en')->label(__('Item'))->weight('semibold')->description(fn (Item $record) => $record->category?->localized('name')),
                TextColumn::make('issues')
                    ->label(__('Missing'))
                    ->state(fn (Item $record) => static::issues($record))
                    ->badge()
                    ->color('warning'),
            ])
            ->recordUrl(fn (Item $record) => ItemResource::getUrl('edit', ['record' => $record]))
            ->recordActions([
                Action::make('fix')
                    ->label(__('Complete it'))
                    ->url(fn (Item $record) => ItemResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading(__('Every item is complete'))
            ->emptyStateDescription(__('Nice. Prices, photos and details are all in place.'))
            ->emptyStateIcon('heroicon-o-check-badge');
    }
}
