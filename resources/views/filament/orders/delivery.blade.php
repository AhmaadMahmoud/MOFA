@php $order = $getRecord(); @endphp
{{-- Day, time and area are in the summary at the top; this card is for finding the door. --}}
<div class="mofa-order-delivery">
    @if ($order->is_pickup)
        <p class="mofa-order-sub">{{ __('The customer collects from the bakery.') }}</p>
    @elseif ($order->address)
        <div class="mofa-order-address">
            <p dir="auto">{{ $order->address }}</p>
            <p class="mofa-order-sub">{{ $order->zoneName() }}</p>
            <a href="https://www.google.com/maps/search/?api=1&query={{ rawurlencode($order->address.', '.$order->zone_name_en.', Egypt') }}" target="_blank" rel="noopener" class="mofa-link">
                {{ __('Open in Maps') }}
            </a>
        </div>
    @endif

    @if ($order->notes)
        <div class="mofa-order-notes">
            <p class="mofa-order-notes-label">{{ __('Customer notes') }}</p>
            <p dir="auto">{{ $order->notes }}</p>
        </div>
    @endif
</div>
