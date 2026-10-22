@php
    use App\Filament\Resources\Orders\OrderResource;

    $order = $getRecord();
    $customer = $order->customer;
    $count = $customer->orders()->count();
    $initials = str($customer->name)->explode(' ')->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->join('');
@endphp
<div class="mofa-order-customer">
    <div class="mofa-order-who">
        <span class="mofa-order-avatar" aria-hidden="true">{{ mb_strtoupper($initials) }}</span>
        <div>
            <p class="mofa-order-name">{{ $customer->name }}</p>
            <a href="{{ OrderResource::getUrl('index', ['tab' => 'all', 'search' => $customer->phone]) }}" class="mofa-order-sub mofa-link">
                {{ trans_choice(':count order|:count orders', $count) }}
            </a>
        </div>
    </div>
    <div class="mofa-order-contact">
        <x-filament::button tag="a" :href="'tel:'.$customer->phone" color="gray" size="sm" icon="heroicon-m-phone">{{ __('Call') }}</x-filament::button>
        <x-filament::button tag="a" :href="'https://wa.me/2'.$customer->phone" target="_blank" color="gray" size="sm" icon="heroicon-m-chat-bubble-left-right">WhatsApp</x-filament::button>
        <x-filament::button tag="a" :href="'mailto:'.$customer->email" color="gray" size="sm" icon="heroicon-m-envelope">{{ __('Email') }}</x-filament::button>
    </div>
    <dl class="mofa-order-list">
        <div><dt>{{ __('Phone') }}</dt><dd dir="ltr" class="mofa-ltr">{{ $customer->phone }}</dd></div>
        <div><dt>{{ __('Email') }}</dt><dd dir="ltr" class="mofa-ltr">{{ $customer->email }}</dd></div>
    </dl>
</div>
