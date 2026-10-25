<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Ordering\OrderWorkflow;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\ViewEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/**
 * @property Order $record
 */
class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('Order').' '.$this->record->number;
    }

    public function getSubheading(): ?string
    {
        return __('Placed :time', ['time' => $this->record->created_at->diffForHumans()]);
    }

    /**
     * Read top to bottom: where the order stands, then what was ordered and how it was paid,
     * with who and where beside it. Each block is a small Blade view in resources/views/filament/orders.
     */
    public function infolist(Schema $schema): Schema
    {
        $view = fn (string $name) => ViewEntry::make($name)->hiddenLabel()->view("filament.orders.{$name}");

        return $schema->components([
            $view('summary')->columnSpanFull(),

            Grid::make(['default' => 1, 'lg' => 3])->columnSpanFull()->schema([
                Group::make([
                    Section::make(__('Items'))->icon(Heroicon::OutlinedShoppingBag)->schema([$view('items')]),
                    Section::make(__('Payment'))->icon(Heroicon::OutlinedBanknotes)->schema([$view('payment')]),
                ])->columnSpan(['lg' => 2]),

                Group::make([
                    Section::make(__('Customer'))->icon(Heroicon::OutlinedUser)->schema([$view('customer')]),
                    Section::make(fn (Order $record) => $record->is_pickup ? __('Pickup') : __('Delivery'))
                        ->icon(Heroicon::OutlinedMapPin)
                        ->schema([$view('delivery')]),
                ]),
            ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        $workflow = app(OrderWorkflow::class);

        return [
            Action::make('confirmPayment')
                ->label(__('Confirm payment'))
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn () => in_array($this->record->status, [OrderStatus::PaymentReview, OrderStatus::PendingPayment, OrderStatus::Expired], true))
                ->requiresConfirmation()
                ->modalHeading(__('Confirm the payment?'))
                ->modalDescription(fn () => __('Check the receipt the customer sent on WhatsApp, and that :amount arrived on InstaPay. The customer gets an email that the order is confirmed.', ['amount' => Money::format($this->record->total)]))
                ->action(function () use ($workflow) {
                    $workflow->confirmPayment($this->record, auth()->user());
                    Notification::make()->success()->title(__('Order confirmed'))->send();
                    $this->refreshRecord();
                }),
            Action::make('outForDelivery')
                ->label(fn () => $this->record->is_pickup ? __('Ready for pickup') : __('Out for delivery'))
                ->icon(Heroicon::OutlinedTruck)
                ->visible(fn () => $this->record->status === OrderStatus::Confirmed)
                ->requiresConfirmation()
                ->action(function () use ($workflow) {
                    $workflow->markOutForDelivery($this->record);
                    $this->refreshRecord();
                }),
            Action::make('delivered')
                ->label(__('Mark delivered'))
                ->icon(Heroicon::OutlinedCheck)
                ->color('success')
                ->visible(fn () => in_array($this->record->status, [OrderStatus::Confirmed, OrderStatus::OutForDelivery], true))
                ->action(function () use ($workflow) {
                    $workflow->markDelivered($this->record);
                    $this->refreshRecord();
                }),
            ActionGroup::make([
                Action::make('cancel')
                    ->label(__('Cancel order'))
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->visible(fn () => $this->record->status->isOpen())
                    ->schema([
                        Textarea::make('reason')->label(__('Reason (sent to the customer)'))->maxLength(255),
                    ])
                    ->modalHeading(__('Cancel this order?'))
                    ->modalDescription(__('If the customer already paid, refund them on InstaPay.'))
                    ->modalSubmitActionLabel(__('Cancel order'))
                    ->action(function (array $data) use ($workflow) {
                        $workflow->cancel($this->record, $data['reason'] ?? null);
                        $this->refreshRecord();
                    }),
            ])->visible(fn () => $this->record->status->isOpen()),
        ];
    }

    private function refreshRecord(): void
    {
        $this->record = $this->record->fresh(['customer', 'items', 'payment', 'payments']);
        $this->cacheSchema('infolist', null);
    }
}
