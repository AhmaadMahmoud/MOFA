<?php

namespace App\Filament\Resources\Items\Schemas;

use App\Enums\ItemBadge;
use App\Filament\Resources\Ingredients\IngredientResource;
use App\Support\Money;
use App\Support\Nutrition;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    Grid::make(1)->columnSpan(['lg' => 2])->schema([
                        static::textSection(),
                        static::photosSection(),
                        static::ingredientsSection(),
                    ]),
                    Grid::make(1)->columnSpan(['lg' => 1])->schema([
                        static::sellingSection(),
                        static::nutritionSection(),
                    ]),
                ]),
        ]);
    }

    protected static function textSection(): Section
    {
        return Section::make(__('Name and description'))
            ->schema([
                Tabs::make('languages')->contained(false)->tabs([
                    Tab::make('English')->schema([
                        TextInput::make('name_en')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255)
                            ->extraInputAttributes(['dir' => 'ltr']),
                        TextInput::make('tagline_en')
                            ->label(__('Tagline'))
                            ->placeholder('Roast beef version')
                            ->maxLength(255)
                            ->extraInputAttributes(['dir' => 'ltr']),
                        Textarea::make('description_en')->label(__('Description'))->rows(4)->extraInputAttributes(['dir' => 'ltr']),
                    ]),
                    Tab::make('العربية')->schema([
                        TextInput::make('name_ar')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255)
                            ->extraInputAttributes(['dir' => 'rtl']),
                        TextInput::make('tagline_ar')
                            ->label(__('Tagline'))
                            ->maxLength(255)
                            ->extraInputAttributes(['dir' => 'rtl']),
                        Textarea::make('description_ar')->label(__('Description'))->rows(4)->extraInputAttributes(['dir' => 'rtl']),
                    ]),
                ]),
            ]);
    }

    protected static function photosSection(): Section
    {
        return Section::make(__('Photos'))
            ->description(__('The first photo is the cover. Drag to reorder.'))
            ->schema([
                FileUpload::make('images')
                    ->hiddenLabel()
                    ->image()
                    ->multiple()
                    ->reorderable()
                    ->appendFiles()
                    ->maxFiles(8)
                    ->maxSize(6 * 1024)
                    ->disk('public')
                    ->directory('items')
                    ->panelLayout('grid')
                    ->imageEditor()
                    ->imageEditorAspectRatioOptions(['4:3', '1:1', null]),
            ]);
    }

    protected static function ingredientsSection(): Section
    {
        return Section::make(__('Ingredients'))
            ->description(__('In the order they should appear, with amounts like 60 g.'))
            ->schema([
                Repeater::make('ingredientLines')
                    ->hiddenLabel()
                    ->relationship()
                    ->orderColumn('sort_order')
                    ->reorderableWithDragAndDrop()
                    ->defaultItems(0)
                    ->addActionLabel(__('Add ingredient'))
                    // One compact row per ingredient line.
                    ->table([
                        TableColumn::make(__('Ingredient'))->markAsRequired(),
                        TableColumn::make(__('Amount'))->width('6.5rem'),
                        TableColumn::make(__('Unit'))->width('6.5rem'),
                    ])
                    ->schema([
                        Select::make('ingredient_id')
                            ->label(__('Ingredient'))
                            ->relationship('ingredient', 'name_en')
                            ->searchable(['name_en', 'name_ar'])
                            ->preload()
                            ->required()
                            ->distinct()
                            ->createOptionForm(IngredientResource::formFields()),
                        TextInput::make('quantity')
                            ->label(__('Amount'))
                            ->numeric()
                            ->minValue(0),
                        Select::make('unit')
                            ->label(__('Unit'))
                            ->options(['g' => 'g', 'ml' => 'ml', 'pc' => __('pc')])
                            ->placeholder('—'),
                    ]),
            ]);
    }

    protected static function sellingSection(): Section
    {
        return Section::make(__('Price and visibility'))
            ->schema([
                Select::make('category_id')
                    ->label(__('Category'))
                    ->relationship('category', 'name_en', fn ($query) => $query->orderBy('sort_order'))
                    ->required()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name_en')->label(__('Name (English)'))->required()->extraInputAttributes(['dir' => 'ltr']),
                        TextInput::make('name_ar')->label(__('Name (Arabic)'))->required()->extraInputAttributes(['dir' => 'rtl']),
                    ]),
                TextInput::make('price')
                    ->label(__('Price'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.5)
                    ->suffix('EGP')
                    ->formatStateUsing(fn (?int $state) => Money::toPounds($state))
                    ->dehydrateStateUsing(fn ($state) => Money::toPiasters($state)),
                Select::make('badge')
                    ->label(__('Badge'))
                    ->options(ItemBadge::class)
                    ->placeholder(__('None')),
                Toggle::make('is_active')->label(__('Shown on the site'))->default(true),
                Toggle::make('is_featured')->label(__('Featured on the home page')),
            ]);
    }

    protected static function nutritionSection(): Section
    {
        $grams = fn (string $name, string $label) => TextInput::make($name)
            ->label(__($label))
            ->numeric()
            ->minValue(0)
            ->step(0.1)
            ->suffix('g');

        return Section::make(__('Nutrition'))
            ->description(__('Per serving. Calories are calculated for you.'))
            ->schema([
                TextInput::make('serving_size_g')->label(__('Serving size'))->numeric()->integer()->minValue(0)->suffix('g')->columnSpanFull(),
                $grams('protein_g', 'Protein')->live(onBlur: true),
                $grams('carbs_g', 'Carbohydrates')->live(onBlur: true),
                $grams('fat_g', 'Fat')->live(onBlur: true),
                $grams('fiber_g', 'Fiber'),
                $grams('sugars_g', 'Sugars'),
                Text::make(function (Get $get) {
                    $kcal = Nutrition::calories($get('protein_g'), $get('carbs_g'), $get('fat_g'));

                    return $kcal === null
                        ? __('Calories: add protein, carbohydrates and fat')
                        : __('Calories: ~:n kcal', ['n' => $kcal]);
                })->columnSpanFull()->weight('semibold'),
            ])
            ->columns(2);
    }
}
