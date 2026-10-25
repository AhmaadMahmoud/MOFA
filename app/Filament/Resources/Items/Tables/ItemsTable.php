<?php

namespace App\Filament\Resources\Items\Tables;

use App\Models\Item;
use App\Support\Money;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('images')
                    ->label('')
                    ->disk('public')
                    ->limit(1)
                    ->square()
                    ->imageSize(56),
                TextColumn::make('name_en')
                    ->label(__('Item'))
                    ->searchable(['name_en', 'name_ar'])
                    ->weight('semibold')
                    ->description(fn (Item $record) => $record->name_ar),
                TextColumn::make('category.name_en')
                    ->label(__('Category'))
                    ->formatStateUsing(fn ($state, Item $record) => $record->category?->localized('name'))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('price')
                    ->label(__('Price'))
                    ->formatStateUsing(fn (int $state) => $state > 0 ? Money::format($state) : '—')
                    ->sortable(),
                TextColumn::make('calories')
                    ->label(__('Calories'))
                    // Number and unit as one translated string, so Arabic reads "555 سعر حراري", never "kcal 555".
                    ->formatStateUsing(fn (int $state) => __(':n kcal', ['n' => $state]))
                    ->placeholder('—')
                    ->toggleable(),
                IconColumn::make('is_featured')
                    ->label(__('Featured'))
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                ToggleColumn::make('is_active')->label(__('Shown')),
            ])
            ->filters([
                SelectFilter::make('category')->label(__('Category'))->relationship('category', 'name_en'),
                TernaryFilter::make('is_active')->label(__('Shown on the site')),
                TernaryFilter::make('is_featured')->label(__('Featured')),
            ])
            ->recordActions([
                EditAction::make()->iconButton()->tooltip(__('Edit')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('No items yet'))
            ->emptyStateDescription(__('Add the first sandwich, bagel or box.'));
    }
}
