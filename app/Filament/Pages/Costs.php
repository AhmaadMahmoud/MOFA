<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Items\ItemResource;
use App\Models\Category;
use App\Models\Item;
use App\Support\Costing;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

/**
 * Every item side by side: what it costs to make, what it sells for, and the margin.
 * Ingredient prices live on the Ingredients page; amounts on each item.
 */
class Costs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'costs';

    protected string $view = 'filament.pages.costs';

    /** @var array<int, array> Costing summaries per item id, worked out once per render. */
    protected array $summaries = [];

    public static function getNavigationGroup(): ?string
    {
        return __('Menu');
    }

    public static function getNavigationLabel(): string
    {
        return __('Costs and profit');
    }

    public function getTitle(): string
    {
        return __('Costs and profit');
    }

    public function getSubheading(): ?string
    {
        return __('Per item, from the ingredient prices and the amounts in each item.');
    }

    protected function summary(Item $item): array
    {
        return $this->summaries[$item->id] ??= Costing::forItem($item);
    }

    public function table(Table $table): Table
    {
        return $table
            // Same order as the menu: categories in their order, items in theirs.
            ->query(Item::query()
                ->with(['category', 'ingredientLines.ingredient'])
                ->orderBy(Category::query()->select('sort_order')->whereColumn('categories.id', 'items.category_id'))
                ->orderBy('category_id')
                ->orderBy('sort_order'))
            ->defaultGroup(Group::make('category_id')
                ->label(__('Category'))
                ->titlePrefixedWithLabel(false)
                ->getTitleFromRecordUsing(fn (Item $record) => $record->category->localized('name'))
                ->orderQueryUsing(fn ($query) => $query))
            ->recordUrl(fn (Item $record) => ItemResource::getUrl('edit', ['record' => $record]))
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->label(__('Item'))
                    ->state(fn (Item $record) => $record->localized('name'))
                    ->weight('semibold')
                    ->description(fn (Item $record) => $this->summary($record)['lines'] && ! $this->summary($record)['complete'] ? __('Some ingredients are missing a cost') : null),
                TextColumn::make('cost')->label(__('Cost per item'))
                    ->state(fn (Item $record) => $this->summary($record)['lines'] ? Costing::money($this->summary($record)['total']) : null)
                    ->placeholder(__('No ingredients'))
                    ->alignEnd(),
                TextColumn::make('price')->label(__('Selling price'))
                    ->state(fn (Item $record) => Costing::money($record->price))
                    ->alignEnd(),
                TextColumn::make('profit')->label(__('Profit per item'))
                    ->state(fn (Item $record) => $this->summary($record)['lines'] ? Costing::money($this->summary($record)['profit']) : null)
                    ->placeholder('—')
                    ->weight('semibold')
                    ->alignEnd(),
                TextColumn::make('margin')->label(__('Margin'))
                    ->state(fn (Item $record) => $this->summary($record)['lines'] && $this->summary($record)['margin'] !== null
                        ? number_format($this->summary($record)['margin'], 0).'%'
                        : null)
                    ->badge()
                    ->color(fn (Item $record) => Costing::marginColor($this->summary($record)['margin']))
                    ->placeholder('—')
                    ->alignEnd(),
            ])
            ->emptyStateHeading(__('No items yet'));
    }
}
