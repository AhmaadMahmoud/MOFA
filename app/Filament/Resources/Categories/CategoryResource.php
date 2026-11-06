<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('Menu');
    }

    public static function getModelLabel(): string
    {
        return __('category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Categories');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name_en')
                ->label(__('Name (English)'))
                ->required()
                ->maxLength(255)
                ->extraInputAttributes(['dir' => 'ltr']),
            TextInput::make('name_ar')
                ->label(__('Name (Arabic)'))
                ->required()
                ->maxLength(255)
                ->extraInputAttributes(['dir' => 'rtl']),
            FileUpload::make('image')
                ->label(__('Category photo'))
                ->helperText(__('Shown on the home page. Leave it empty to use the photo of the first item in the category.'))
                ->image()
                ->maxSize(6 * 1024)
                ->disk('public')
                ->directory('categories')
                ->imageEditor()
                ->imageEditorAspectRatioOptions(['4:3', '1:1', null]),
            Toggle::make('is_active')
                ->label(__('Shown on the site'))
                ->default(true),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image')->label(__('Photo'))->disk('public')->square()->size(44)
                    ->defaultImageUrl(fn (Category $record) => $record->coverUrl()),
                TextColumn::make('name_en')->label(__('Name (English)'))->searchable()->weight('semibold'),
                TextColumn::make('name_ar')->label(__('Name (Arabic)'))->searchable(),
                TextColumn::make('items_count')->label(__('Items'))->counts('items')->badge(),
                ToggleColumn::make('is_active')->label(__('Shown')),
            ])
            ->recordActions([
                EditAction::make()->iconButton()->tooltip(__('Edit')),
                DeleteAction::make()->iconButton()
                    ->disabled(fn (Category $record) => $record->items()->exists())
                    ->tooltip(fn (Category $record) => $record->items()->exists() ? __('Move or delete its items first') : __('Delete')),
            ])
            ->emptyStateHeading(__('No categories yet'))
            ->emptyStateDescription(__('Start with groups like Sandwiches, Bagels or Boxes.'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }
}
