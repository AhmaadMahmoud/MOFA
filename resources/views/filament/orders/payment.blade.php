@php
    use App\Enums\OrderStatus;
    use App\Support\Money;

    $order = $getRecord();
    $payment = $order->payment;
    $waiting = in_array($order->status, [OrderStatus::PendingPayment, OrderStatus::PaymentReview, OrderStatus::Expired], true);
    $chat = 'https://wa.me/2'.$order->customer->phone.'?text='.rawurlencode(__('Hi :name, about your MOFA order :number', ['name' => $order->customer->name, 'number' => $order->number], $order->locale));
@endphp
<div @class(['mofa-order-payment', 'has-receipt' => $waiting])>
    <dl class="mofa-order-list">
        <div><dt>{{ __('Method') }}</dt><dd>InstaPay</dd></div>
        <div><dt>{{ __('Amount to pay') }}</dt><dd>{{ Money::format($payment?->amount ?? $order->total) }}</dd></div>
        <div><dt>{{ __('Status') }}</dt><dd><x-filament::badge :color="$payment?->status->getColor() ?? 'gray'">{{ $payment?->status->getLabel() }}</x-filament::badge></dd></div>
        @if ($payment?->reviewed_at)
            <div><dt>{{ __('Confirmed') }}</dt><dd>{{ $payment->reviewed_at->diffForHumans() }}@if ($payment->reviewer) · {{ $payment->reviewer->name }}@endif</dd></div>
        @endif
    </dl>

    @if ($waiting)
        {{-- No upload on the site: the customer sends the receipt on WhatsApp. --}}
        <div class="mofa-order-wa">
            <p>{{ __('The customer sends the InstaPay receipt on WhatsApp. Check it, then confirm the payment. If it never comes, cancel the order to free its place.') }}</p>
            <x-filament::button tag="a" :href="$chat" target="_blank" color="gray" size="sm" icon="heroicon-m-chat-bubble-left-right">
                {{ __('Open the chat') }}
            </x-filament::button>
        </div>
    @endif
</div>
