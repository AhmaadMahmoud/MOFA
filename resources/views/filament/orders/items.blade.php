@php
    use App\Support\Money;
    $order = $getRecord();
@endphp
<div class="mofa-order-items">
    <table>
        <thead>
            <tr>
                <th>{{ __('Item') }}</th>
                <th class="mofa-num">{{ __('Qty') }}</th>
                <th class="mofa-num">{{ __('Price') }}</th>
                <th class="mofa-num">{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $line)
                <tr>
                    <td class="mofa-order-item-name">{{ $line->localized('name') }}</td>
                    <td class="mofa-num">× {{ $line->quantity }}</td>
                    <td class="mofa-num mofa-muted">{{ Money::format($line->unit_price) }}</td>
                    <td class="mofa-num">{{ Money::format($line->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <dl class="mofa-order-totals">
        <div><dt>{{ __('Subtotal') }}</dt><dd>{{ Money::format($order->subtotal) }}</dd></div>
        <div><dt>{{ __('Delivery fee') }}</dt><dd>{{ $order->delivery_fee > 0 ? Money::format($order->delivery_fee) : __('Free') }}</dd></div>
        <div class="is-total"><dt>{{ __('Total') }}</dt><dd>{{ Money::format($order->total) }}</dd></div>
    </dl>
</div>
