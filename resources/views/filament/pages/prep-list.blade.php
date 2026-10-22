@php
    use App\Support\Money;

    $orders = $this->orders();
    $totals = $this->totals();
    $review = $this->reviewCount();
    $bySlot = $orders->groupBy(fn ($o) => $o->slotLabel());
@endphp
<x-filament-panels::page>
    <div class="mofa-prep">
        {{-- Day picker --}}
        <div class="mofa-prep-days mofa-no-print">
            <x-filament::icon-button icon="heroicon-m-chevron-left" class="rtl:rotate-180" wire:click="shift(-1)" :label="__('Previous day')" />
            <input type="date" wire:model.live="date" class="mofa-prep-date" aria-label="{{ __('Delivery day') }}">
            <x-filament::icon-button icon="heroicon-m-chevron-right" class="rtl:rotate-180" wire:click="shift(1)" :label="__('Next day')" />
            <x-filament::button color="gray" size="sm" wire:click="$set('date', '{{ now()->addDay()->toDateString() }}')">{{ __('Tomorrow') }}</x-filament::button>
        </div>

        @if ($review > 0)
            <div class="mofa-prep-note mofa-no-print">
                {{ trans_choice(':count order for this day is waiting for you to confirm its payment.|:count orders for this day are waiting for you to confirm their payment.', $review) }}
                <a href="{{ \App\Filament\Resources\Orders\OrderResource::getUrl('index', ['tab' => 'unpaid']) }}">{{ __('Confirm them') }}</a>
            </div>
        @endif

        @if ($totals->isEmpty())
            <x-filament::section>
                <div class="mofa-prep-empty">
                    <p class="mofa-prep-empty-title">{{ __('Nothing to bake for this day yet') }}</p>
                    <p>{{ __('Confirmed orders appear here.') }}</p>
                </div>
            </x-filament::section>
        @else
            <x-filament::section :heading="__('To bake')" :description="trans_choice(':count order|:count orders', $orders->count())">
                <table class="mofa-prep-table">
                    <thead>
                        <tr>
                            <th>{{ __('Item') }}</th>
                            <th class="mofa-num">{{ __('Confirmed') }}</th>
                            @if ($review > 0)
                                <th class="mofa-num">{{ __('Not confirmed') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($totals as $row)
                            <tr>
                                <td>{{ $row['name'] }}</td>
                                <td class="mofa-num mofa-strong">{{ $row['confirmed'] }}</td>
                                @if ($review > 0)
                                    <td class="mofa-num mofa-muted">{{ $row['review'] ? '+'.$row['review'] : '—' }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-filament::section>

            @foreach ($bySlot as $slot => $slotOrders)
                <x-filament::section :heading="$slot" :description="trans_choice(':count order|:count orders', $slotOrders->count())">
                    <ol class="mofa-prep-orders">
                        @foreach ($slotOrders as $order)
                            <li class="mofa-prep-order">
                                <div class="mofa-prep-order-head">
                                    <a href="{{ \App\Filament\Resources\Orders\OrderResource::getUrl('view', ['record' => $order]) }}" class="mofa-strong" dir="ltr">{{ $order->number }}</a>
                                    <span>{{ $order->customer->name }}</span>
                                    <span dir="ltr">{{ $order->customer->phone }}</span>
                                    <x-filament::badge :color="$order->status->getColor()" size="sm">{{ $order->status->getLabel() }}</x-filament::badge>
                                </div>
                                <div class="mofa-muted">
                                    {{ $order->zoneName() }}@if (! $order->is_pickup && $order->address) · <span dir="auto">{{ $order->address }}</span>@endif
                                </div>
                                <div>
                                    @foreach ($order->items as $line)
                                        <span class="mofa-prep-chip">{{ $line->quantity }} × {{ $line->localized('name') }}</span>
                                    @endforeach
                                </div>
                                @if ($order->notes)
                                    <div class="mofa-prep-notes" dir="auto">{{ $order->notes }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </x-filament::section>
            @endforeach
        @endif
    </div>
</x-filament-panels::page>
