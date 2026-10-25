<?php

namespace App\Filament\Resources\Ingredients;

use App\Filament\Resources\Ingredients\Pages\ManageIngredients;
use App\Models\Ingredient;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
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
