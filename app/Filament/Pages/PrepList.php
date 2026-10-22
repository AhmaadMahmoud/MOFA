<?php

namespace App\Filament\Pages;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

/** What to bake and deliver on one day. Printable. */
class PrepList extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'prep-list';

    protected string $view = 'filament.pages.prep-list';

    #[Url]
    public ?string $date = null;

    public static function getNavigationGroup(): ?string
    {
        return __('Orders');
    }

    public static function getNavigationLabel(): string
    {
        return __('Prep list');
    }

    public function getTitle(): string
    {
        return __('Prep list');
    }

    public function getSubheading(): ?string
    {
        return $this->day()->translatedFormat('l j F Y');
    }

    public function mount(): void
    {
        $this->date = $this->parse($this->date)->toDateString();
    }

    public function day(): CarbonImmutable
    {
        return $this->parse($this->date);
    }

    public function shift(int $days): void
    {
        $this->date = $this->day()->addDays($days)->toDateString();
    }

    public function updatedDate(): void
    {
        $this->date = $this->parse($this->date)->toDateString();
    }

    private function parse(?string $date): CarbonImmutable
    {
        try {
            return $date ? CarbonImmutable::createFromFormat('!Y-m-d', $date) : CarbonImmutable::tomorrow();
        } catch (\Throwable) {
            return CarbonImmutable::tomorrow();
        }
    }

    /** @return Collection<int, Order> confirmed orders, by slot then area */
    public function orders(): Collection
    {
        return Order::query()
            ->forDate($this->day())
            ->whereIn('status', OrderStatus::toBake())
            ->with(['customer', 'items', 'slot'])
            ->get()
            ->sortBy([
                fn (Order $a, Order $b) => [$a->slot?->starts_at, $a->slot_label_en] <=> [$b->slot?->starts_at, $b->slot_label_en],
                fn (Order $a, Order $b) => [$a->zone_name_en, $a->number] <=> [$b->zone_name_en, $b->number],
            ])
            ->values();
    }

    /** Orders for this day whose payment Faten hasn't confirmed yet (receipt on WhatsApp). */
    public function reviewCount(): int
    {
        return Order::query()->forDate($this->day())->awaitingConfirmation()->count();
    }

    /**
     * Quantities to bake: confirmed, plus what is waiting for payment confirmation (shown apart).
     *
     * @return Collection<int, array{name: string, confirmed: int, review: int}>
     */
    public function totals(): Collection
    {
        $waitingIds = Order::query()->forDate($this->day())->awaitingConfirmation()->pluck('id')->all();

        $rows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereDate('orders.delivery_date', $this->day())
            ->where(fn ($query) => $query->whereIn('orders.status', array_map(fn ($s) => $s->value, OrderStatus::toBake()))->orWhereIn('orders.id', $waitingIds))
            ->get(['order_items.item_id', 'order_items.name_en', 'order_items.name_ar', 'order_items.quantity', 'order_items.order_id']);

        return $rows
            ->groupBy(fn ($r) => $r->item_id ?? 'name:'.$r->name_en)
            ->map(function (Collection $lines) use ($waitingIds) {
                $first = $lines->first();
                $review = $lines->filter(fn ($l) => in_array($l->order_id, $waitingIds, true))->sum('quantity');

                return [
                    'name' => $first->localized('name'),
                    'confirmed' => $lines->sum('quantity') - $review,
                    'review' => $review,
                ];
            })
            ->sortByDesc('confirmed')
            ->values();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label(__('Print'))
                ->icon(Heroicon::OutlinedPrinter)
                ->color('gray')
                ->alpineClickHandler('window.print()'),
        ];
    }
}
