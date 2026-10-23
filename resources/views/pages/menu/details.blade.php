{{-- The item's details, all visible at once like a printed menu: the description, a strip of numbers, the ingredients as one line. --}}
<section class="flex flex-col gap-5 short:gap-4">
    @if ($description)
        <p class="m-0 text-base leading-relaxed short:line-clamp-2 lg:text-lg">{{ $description }}</p>
    @endif

    {{-- Column count follows the numbers shown: grid-cols-1 grid-cols-2 grid-cols-3 grid-cols-4 (listed so Tailwind builds them). --}}
    @if ($item->calories || $macros->isNotEmpty())
        <dl @class(['m-0 grid divide-x divide-brown/15 border-y border-brown/15 py-3 rtl:divide-x-reverse', 'grid-cols-'.(($item->calories ? 1 : 0) + $macros->count())])>
            @if ($item->calories)
                <div class="flex flex-col items-center gap-0.5 text-center">
                    <dd class="m-0 font-serif text-[1.75rem] leading-none font-bold">{{ $item->calories }}</dd>
                    <dt class="text-xs text-ink-muted">{{ __('calories') }}</dt>
                </div>
            @endif
            @foreach ($macros as $m)
                <div class="flex flex-col items-center gap-0.5 text-center">
                    <dd class="m-0 font-serif text-[1.75rem] leading-none font-bold">{{ $grams($m['grams']) }}<span class="font-sans text-sm font-normal">{{ __('g') }}</span></dd>
                    <dt class="text-xs text-ink-muted">{{ $m['label'] }}</dt>
                </div>
            @endforeach
        </dl>
        @php
            $extras = array_filter([
                $item->serving_size_g ? __('Per serving of about :g g', ['g' => $item->serving_size_g]) : null,
                $item->fiber_g !== null ? __('Fiber').' '.$grams($item->fiber_g).' '.__('g') : null,
                $item->sugars_g !== null ? __('Sugars').' '.$grams($item->sugars_g).' '.__('g') : null,
            ]);
        @endphp
        @if ($extras)
            <p class="m-0 -mt-3 text-xs text-ink-muted">{{ implode(' · ', $extras) }}. {{ __('Approximate values.') }}</p>
        @endif
    @endif

    @if ($item->ingredientLines->isNotEmpty())
        <div>
            <h2 class="m-0 mb-1 text-sm font-semibold">{{ __("What's inside") }}</h2>
            <p class="m-0 leading-relaxed text-ink-muted short:line-clamp-2">
                @foreach ($item->ingredientLines as $line)
                    <span class="text-brown">{{ $line->ingredient->localized('name') }}</span>@if ($amount = $line->amountLabel()) <span class="text-sm">({{ $amount }})</span>@endif{{ $loop->last ? '.' : __(', ') }}
                @endforeach
            </p>
        </div>
    @endif

    {{-- Phones skip this line so the whole item still fits on one screen. --}}
    <p class="m-0 hidden items-center gap-2 text-sm text-ink-muted sm:flex">
        <x-mofa.icon name="calendar" :size="18" class="shrink-0" />
        {{ __('Baked to order. Order today, it reaches you tomorrow morning.') }}
    </p>
</section>
