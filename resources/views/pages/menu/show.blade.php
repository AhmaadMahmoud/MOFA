@php($images = $item->imageUrls())
<x-layouts.app :title="$item->localized('name')" :description="$item->localized('description')">
    <div class="mx-auto max-w-(--container-site) px-5 pt-2 lg:px-10 lg:pt-6">
        <a href="{{ route('menu') }}#{{ $item->category->slug }}" class="inline-flex min-h-11 items-center gap-2 text-ink-muted no-underline hover:text-brown">
            <x-mofa.icon name="arrow" :size="18" class="rotate-180 rtl:rotate-0" />
            {{ __('Back to the menu') }}
        </a>
    </div>

    <article class="mx-auto grid max-w-(--container-site) gap-6 pb-16 lg:grid-cols-[1.1fr_1fr] lg:gap-12 lg:px-10 lg:pt-4">
        {{-- Photos: swipe on phones, stacked thumbnails on desktop. --}}
        <div x-data="{ i: 0, n: {{ max(count($images), 1) }} }" class="relative lg:sticky lg:top-6 lg:self-start">
            @if ($images)
                <div x-ref="track" @scroll.debounce.50ms="i = Math.round(Math.abs($el.scrollLeft) / $el.clientWidth)"
                     class="flex snap-x snap-mandatory overflow-x-auto [scrollbar-width:none] lg:rounded-md [&::-webkit-scrollbar]:hidden">
                    @foreach ($images as $n => $src)
                        <img src="{{ $src }}" alt="{{ $n === 0 ? $item->localized('name') : '' }}" @if ($n > 0) loading="lazy" @endif decoding="async"
                             class="aspect-[4/3] w-full shrink-0 snap-center object-cover">
                    @endforeach
                </div>
                @if (count($images) > 1)
                    <div class="absolute inset-x-0 bottom-3 flex justify-center gap-2" aria-hidden="true">
                        @foreach ($images as $n => $src)
                            <span class="h-2 rounded-pill bg-paper transition-all" :class="i === {{ $n }} ? 'w-6' : 'w-2 opacity-60'"></span>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="flex aspect-[4/3] w-full items-center justify-center bg-paper lg:rounded-md" aria-hidden="true">
                    <img src="{{ asset('images/brand/mofa-header-logo.png') }}" alt="" class="h-20 w-auto opacity-50">
                </div>
            @endif
        </div>

        <div class="flex flex-col gap-6 px-5 lg:px-0">
            <header class="flex flex-col gap-2">
                @if ($item->badge)
                    <x-tag class="self-start">{{ $item->badge->getLabel() }}</x-tag>
                @endif
                <h1 class="m-0 font-serif text-[2.5rem] leading-[1.05] font-bold uppercase tracking-[0.02em] text-balance lg:text-[3.25rem] rtl:normal-case rtl:tracking-normal">{{ $item->localized('name') }}</h1>
                @if ($tagline = $item->localized('tagline'))
                    <div class="flex items-center gap-3 text-ink-muted">
                        <span class="h-px w-8 bg-brown/40" aria-hidden="true"></span>
                        <p class="m-0 font-serif text-xl italic">{{ $tagline }}</p>
                        <span class="h-px w-8 bg-brown/40" aria-hidden="true"></span>
                    </div>
                @endif
                <p class="m-0 mt-1 font-serif text-[1.75rem] font-bold" dir="auto">{{ $item->formattedPrice() }}</p>
            </header>

            @if ($description = $item->localized('description'))
                <p class="m-0 text-lg leading-relaxed">{{ $description }}</p>
            @endif

            @if ($item->hasNutrition())
                <section class="overflow-hidden rounded-md border border-brown/25" aria-labelledby="nutrition-title">
                    <div class="flex items-center justify-between gap-4 border-b border-brown/25 bg-paper px-5 py-4">
                        <h2 id="nutrition-title" class="eyebrow m-0 font-semibold">{{ __('Nutritional information') }}</h2>
                        @if ($item->serving_size_g)
                            <span class="text-sm text-ink-muted">{{ __('Serving size: ~:g g', ['g' => $item->serving_size_g]) }}</span>
                        @endif
                    </div>
                    <dl class="m-0 grid grid-cols-2 sm:grid-cols-3">
                        @if ($item->calories)
                            <div class="flex flex-col gap-0.5 border-b border-e border-brown/15 px-5 py-3">
                                <dt class="text-sm text-ink-muted">{{ __('Calories') }}</dt>
                                <dd class="m-0 font-serif text-xl font-bold">~{{ __(':n kcal', ['n' => $item->calories]) }}</dd>
                            </div>
                        @endif
                        @foreach (\App\Models\Item::NUTRIENTS as $column => $label)
                            @continue($item->{$column} === null)
                            <div class="flex flex-col gap-0.5 border-b border-e border-brown/15 px-5 py-3">
                                <dt class="text-sm text-ink-muted">{{ __($label) }}</dt>
                                <dd class="m-0 font-serif text-xl font-bold">~{{ (float) $item->{$column} + 0 }} {{ __('g') }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    <p class="m-0 px-5 py-3 text-xs text-ink-muted">{{ __('Approximate values per serving. Calories are calculated from protein, carbohydrates and fat.') }}</p>
                </section>
            @endif

            @if ($item->ingredientLines->isNotEmpty())
                <section aria-labelledby="ingredients-title" class="flex flex-col gap-3">
                    <h2 id="ingredients-title" class="eyebrow m-0 font-semibold">{{ __('Ingredients') }}</h2>
                    <ul class="m-0 list-none divide-y divide-brown/15 border-y border-brown/15 p-0">
                        @foreach ($item->ingredientLines as $line)
                            <li class="flex items-baseline justify-between gap-4 py-2.5">
                                <span class="font-serif text-lg">{{ $line->ingredient->localized('name') }}</span>
                                @if ($amount = $line->amountLabel())
                                    <span class="shrink-0 text-sm text-ink-muted" dir="auto">{{ $amount }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </article>

    @if ($more->isNotEmpty())
        <section class="bg-paper py-12 lg:py-16" aria-labelledby="more-title">
            <div class="mx-auto flex max-w-(--container-site) flex-col gap-6 px-4 lg:px-10">
                <h2 id="more-title" class="m-0 font-serif text-[1.75rem] font-bold">{{ __('More from :category', ['category' => $item->category->localized('name')]) }}</h2>
                <div class="grid gap-3 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3">
                    @foreach ($more as $other)
                        <x-menu.item-card :item="$other" class="bg-cream" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.app>
