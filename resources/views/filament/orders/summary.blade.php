@php
    use App\Enums\OrderStatus;
    use App\Support\Money;

    $order = $getRecord();
    $status = $order->status;
    // The road an order travels. Each step: label, reached?, when.
    // The receipt comes on WhatsApp, so the next step after "placed" is Faten confirming the payment.
    $rank = match ($status) {
        OrderStatus::PendingPayment, OrderStatus::PaymentReview => 0,
        OrderStatus::Confirmed => 1,
        OrderStatus::OutForDelivery => 2,
        OrderStatus::Delivered => 3,
        default => -1,
    };
    $steps = [
        [__('Placed'), $order->created_at],
        [__('Paid and confirmed'), $order->confirmed_at],
        [$order->is_pickup ? __('Ready for pickup') : __('Out for delivery'), null],
        [__('Delivered'), null],
    ];
    $stopped = in_array($status, [OrderStatus::Expired, OrderStatus::Cancelled], true);
@endphp
<div class="mofa-order-summary">
    @if ($stopped)
        <div class="mofa-order-stopped">
            <x-filament::badge :color="$status->getColor()" size="lg">{{ $status->getLabel() }}</x-filament::badge>
            <span>
                @if ($status === OrderStatus::Cancelled)
                    {{ $order->cancelled_at?->translatedFormat('D j M, g:i A') }}@if ($order->cancel_reason) · {{ $order->cancel_reason }}@endif
                @else
                    {{ __('The payment didn\'t arrive.') }}
                @endif
            </span>
        </div>
    @else
        <ol class="mofa-order-steps" aria-label="{{ __('Order progress') }}">
            @foreach ($steps as $i => [$label, $at])
                <li @class(['is-done' => $i < $rank, 'is-current' => $i === $rank]) @if ($i === $rank) aria-current="step" @endif>
                    <span class="mofa-order-step-dot" aria-hidden="true">
                        @if ($i < $rank)
                            <x-filament::icon icon="heroicon-m-check" class="size-3.5" />
                        @endif
                    </span>
                    <span class="mofa-order-step-label">{{ $label }}</span>
                    @if ($at && $i <= $rank)
                        <span class="mofa-order-step-time">{{ $at->translatedFormat('D j M, g:i A') }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    @endif

    <dl class="mofa-order-facts">
        <div>
            <dt>{{ $order->is_pickup ? __('Pickup') : __('Delivery') }}</dt>
            <dd>{{ $order->delivery_date->translatedFormat('l j F') }}</dd>
            <dd class="mofa-order-sub">{{ $order->slotLabel() }}</dd>
        </div>
        <div>
            <dt>{{ __('Area') }}</dt>
            <dd>{{ $order->zoneName() }}</dd>
            <dd class="mofa-order-sub">{{ $order->delivery_fee > 0 ? Money::format($order->delivery_fee) : __('Free') }}</dd>
        </div>
        <div>
            <dt>{{ __('Total') }}</dt>
            <dd class="mofa-order-total">{{ Money::format($order->total) }}</dd>
            <dd class="mofa-order-sub">{{ trans_choice(':count item|:count items', $order->items->sum('quantity')) }}</dd>
        </div>
        <div>
            <dt>{{ __('Payment') }}</dt>
            <dd><x-filament::badge :color="$order->payment?->status->getColor() ?? 'gray'">{{ $order->payment?->status->getLabel() }}</x-filament::badge></dd>
            @if (in_array($status, [OrderStatus::PendingPayment, OrderStatus::PaymentReview], true))
                <dd class="mofa-order-sub">{{ __('Waiting for the receipt on WhatsApp') }}</dd>
            @endif
        </div>
    </dl>
</div>
