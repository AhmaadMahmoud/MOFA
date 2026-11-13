<?php

namespace App\Filament\Widgets\Visitors;

use App\Analytics\Labels;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Visit;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/** Every visit in the period, newest first; open one to see the path it took. */
class RecentVisits extends TableWidget
{
    use ReadsPeriod;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $report = $this->report();

        return $table
            ->heading(__('Visits'))
            ->description(__('Each visitor is anonymous. Open a visit to see the pages it went through.'))
            ->query(Visit::query()
                ->whereBetween('started_at', [$report->from, $report->to])
                ->with('order:id,number')
                ->latest('started_at'))
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('started_at')->label(__('Visit time'))
                    ->since()->dateTimeTooltip('D j M, g:i A'),
                TextColumn::make('source')->label(__('Came from'))
                    ->formatStateUsing(fn (string $state) => Labels::source($state))->badge()->color('gray'),
                TextColumn::make('device')->label(__('Device'))
                    ->formatStateUsing(fn (string $state, Visit $record) => Labels::device($state).($record->browser ? ' · '.$record->browser : ''))
                    ->icon(fn (string $state) => match ($state) {
                        'mobile' => Heroicon::OutlinedDevicePhoneMobile,
                        'tablet' => Heroicon::OutlinedDeviceTablet,
                        default => Heroicon::OutlinedComputerDesktop,
                    }),
                // Paths read left to right ("/ar/menu"), also on the Arabic dashboard.
                TextColumn::make('landing_path')->label(__('Landed on'))->color('gray')
                    ->formatStateUsing(fn (string $state) => "\u{2066}".str($state)->limit(28)."\u{2069}"),
                TextColumn::make('page_views')->label(__('Pages'))->alignEnd(),
                TextColumn::make('returning')->label(__('Returning'))
                    ->state(fn (Visit $record) => $record->is_returning ? __('Returning visitor') : null)
                    ->badge()->color('success')->placeholder('—'),
                TextColumn::make('order.number')->label(__('Resulting order'))
                    ->badge()->color('success')->placeholder('—')
                    ->url(fn (Visit $record) => $record->order ? OrderResource::getUrl('view', ['record' => $record->order]) : null),
            ])
            ->filters([
                SelectFilter::make('source')->label(__('Came from'))
                    ->options(collect(\App\Analytics\Source::ALL)->mapWithKeys(fn ($s) => [$s => Labels::source($s)])->all()),
                TernaryFilter::make('ordered')->label(__('Ordered'))
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('order_id'),
                        false: fn (Builder $q) => $q->whereNull('order_id'),
                    ),
            ])
            ->recordActions([
                Action::make('journey')
                    ->label(__('Path'))
                    ->icon(Heroicon::OutlinedMap)
                    ->iconButton()
                    ->tooltip(__('Path'))
                    ->modalHeading(__('The visit, step by step'))
                    ->modalContent(fn (Visit $record) => view('filament.visitors.journey', ['visit' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('Close window'))
                    ->slideOver(),
            ])
            ->recordAction('journey')
            ->emptyStateHeading(__('No visits in this period yet'))
            ->emptyStateIcon(Heroicon::OutlinedUsers);
    }
}
