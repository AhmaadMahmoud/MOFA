@php
    use App\Analytics\Labels;
    use App\Filament\Resources\Orders\OrderResource;

    $visit->loadMissing(['pageViews.item', 'events.item', 'order']);
    $steps = $visit->pageViews->map(fn ($view) => [
        'at' => $view->viewed_at,
        'icon' => 'heroicon-o-document-text',
        'text' => $view->item ? __('Viewed :item', ['item' => $view->item->localized('name')]) : Labels::page($view->page),
    ])->concat($visit->events->map(fn ($event) => [
        'at' => $event->created_at,
        'icon' => $event->name === 'order_placed' ? 'heroicon-o-check-badge' : 'heroicon-o-shopping-bag',
        'text' => $event->name === 'order_placed'
            ? __('Placed the order')
            : __('Added :qty × :item to the bag', ['qty' => $event->quantity, 'item' => $event->item?->localized('name') ?? __('an item')]),
        'highlight' => true,
    ]))->sortBy(fn ($s) => $s['at']->timestamp)->values();
@endphp
<div class="space-y-5 text-sm">
    <dl class="grid grid-cols-2 gap-3 rounded-xl bg-[color:var(--mofa-paper)] p-4">
        <div><dt class="text-gray-500">{{ __('Came from') }}</dt><dd class="font-medium">{{ Labels::source($visit->source) }}@if ($visit->utm_campaign) · {{ $visit->utm_campaign }}@endif</dd></div>
        <div><dt class="text-gray-500">{{ __('Device') }}</dt><dd class="font-medium">{{ Labels::device($visit->device) }}@if ($visit->browser) · {{ $visit->browser }}@endif</dd></div>
        <div><dt class="text-gray-500">{{ __('Started') }}</dt><dd class="font-medium">{{ $visit->started_at->translatedFormat('D j M, g:i A') }}</dd></div>
        <div><dt class="text-gray-500">{{ __('Visitor') }}</dt><dd class="font-medium">{{ $visit->is_returning ? __('Returning visitor') : __('First visit') }}</dd></div>
        @if ($visit->order)
            <div class="col-span-2"><dt class="text-gray-500">{{ __('Resulting order') }}</dt><dd><a class="font-semibold text-[color:var(--mofa-terracotta)] underline" href="{{ OrderResource::getUrl('view', ['record' => $visit->order]) }}">{{ $visit->order->number }}</a></dd></div>
        @endif
    </dl>

    <ol class="relative space-y-4 border-s border-[color:var(--mofa-hairline)] ps-5">
        @foreach ($steps as $step)
            <li class="relative">
                <span @class([
                    'absolute -start-[1.65rem] top-0.5 flex h-5 w-5 items-center justify-center rounded-full ring-4 ring-[color:var(--mofa-surface)]',
                    'bg-[color:var(--mofa-terracotta)] text-white' => $step['highlight'] ?? false,
                    'bg-[color:var(--mofa-cream)] text-[color:var(--mofa-brown)]' => ! ($step['highlight'] ?? false),
                ])>
                    <x-filament::icon :icon="$step['icon']" class="h-3 w-3" />
                </span>
                <p @class(['font-semibold' => $step['highlight'] ?? false])>{{ $step['text'] }}</p>
                <p class="text-xs text-gray-500">{{ $step['at']->translatedFormat('g:i A') }}</p>
            </li>
        @endforeach
    </ol>
</div>
