<?php

namespace App\Filament\Resources\ClosedDates;

use App\Filament\Resources\ClosedDates\Pages\ManageClosedDates;
use App\Models\ClosedDate;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClosedDateResource extends Resource
{
    protected static ?string $model = ClosedDate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('Delivery');
    }

    public static function getModelLabel(): string
    {
        return __('closed day');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Closed days');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('date')
                ->label(__('Date'))
                ->native(false)
                ->displayFormat('D j M Y')
                ->minDate(today())
                ->required()
                ->unique(ignoreRecord: true),
            TextInput::make('note')->label(__('Note'))->placeholder(__('e.g. Eid holiday'))->maxLength(255),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('date', '>=', today()))
            ->defaultSort('date')
            ->columns([
                TextColumn::make('date')->label(__('Date'))->date('l j F Y')->weight('semibold'),
                TextColumn::make('note')->label(__('Note'))->placeholder('—'),
            ])
            ->recordActions([EditAction::make()->iconButton()->tooltip(__('Edit')), DeleteAction::make()->iconButton()->tooltip(__('Delete'))])
            ->emptyStateHeading(__('No closed days coming up'))
            ->emptyStateDescription(__('Add holidays or days off, and customers won\'t be able to pick them.'));
    }

    public static function getPages(): array
    {
        return ['index' => ManageClosedDates::route('/')];
    }
}
