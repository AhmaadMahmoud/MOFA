@php
    $images = $item->imageUrls();
    $name = $item->localized('name');
    $description = $item->localized('description');

    // Where the calories come from: protein and carbs give 4 kcal per gram, fat 9.
    $macros = collect([
        ['label' => __('Protein'), 'grams' => $item->protein_g, 'kcal' => 4, 'swatch' => 'bg-terracotta'],
        ['label' => __('Carbohydrates'), 'grams' => $item->carbs_g, 'kcal' => 4, 'swatch' => 'bg-olive'],
        ['label' => __('Fat'), 'grams' => $item->fat_g, 'kcal' => 9, 'swatch' => 'bg-mauve'],
    ])->filter(fn ($m) => $m['grams'] !== null);
    $macroKcal = $macros->sum(fn ($m) => (float) $m['grams'] * $m['kcal']);
    $macros = $macros->map(fn ($m) => $m + ['share' => $macroKcal > 0 ? round((float) $m['grams'] * $m['kcal'] / $macroKcal * 100) : 0]);
    $grams = fn ($v) => (float) $v + 0;

@endphp
<x-layouts.app :title="$name" :description="$description">
    <article class="relative isolate overflow-hidden">
        {{-- Corner waves, as on the posts. --}}
        <svg class="pointer-events-none absolute end-0 top-0 -z-10 h-28 w-12 lg:h-80 lg:w-28 xl:w-40 rtl:-scale-x-100" viewBox="0 0 110 150" preserveAspectRatio="none" aria-hidden="true">
            <path class="fill-olive" d="M110 0 V150 C70 140 50 110 38 72 C28 40 16 16 0 0 Z" />
        </svg>
        <svg class="pointer-events-none absolute start-0 bottom-0 -z-10 hidden h-72 w-40 lg:block rtl:-scale-x-100" viewBox="0 0 150 224" preserveAspectRatio="none" aria-hidden="true">
            <path class="fill-terracotta" d="M0 0 C40 -20 60 20 70 70 C80 120 110 170 150 224 H0 Z" />
            <path class="fill-mauve" d="M0 80 C26 80 38 120 46 160 C54 192 70 212 90 224 H0 Z" />
        </svg>

        {{-- One screen: the header plus this block fill the viewport (with the bottom bar on phones). --}}
        <div class="mx-auto flex max-w-(--container-site) flex-col px-5 pb-4 lg:min-h-[calc(100dvh-6rem)] lg:justify-center lg:px-10 lg:pb-10">
            <a href="{{ route('menu') }}#{{ $item->category->slug }}" class="inline-flex min-h-10 items-center gap-2 self-start text-sm text-ink-muted no-underline hover:text-brown short:hidden">
                <x-mofa.icon name="arrow" :size="16" class="rotate-180 rtl:rotate-0" />
                {{ __('Back to the menu') }}
            </a>

            <div class="grid items-center gap-5 pt-1 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:gap-14 lg:pt-3">
                {{-- ---------- The photo ---------- --}}
                <div x-data="{ i: 0, go(n) { this.i = n; $refs.track.scrollTo({ left: n * $refs.track.clientWidth * (document.dir === 'rtl' ? -1 : 1), behavior: 'smooth' }) } }"
                     class="relative">
                    <figure class="relative m-0">
                        <div class="overflow-hidden rounded-md bg-paper">
                            @if ($images)
                                <div x-ref="track" @scroll.debounce.60ms="i = Math.round(Math.abs($el.scrollLeft) / $el.clientWidth)"
                                     class="flex aspect-[2/1] short:aspect-[5/2] sm:aspect-[16/10] snap-x snap-mandatory overflow-x-auto [scrollbar-width:none] lg:aspect-[4/3] [&::-webkit-scrollbar]:hidden">
                                    @foreach ($images as $n => $src)
                                        <img src="{{ $src }}" alt="{{ $n === 0 ? $name : '' }}" @if ($n > 0) loading="lazy" @else fetchpriority="high" @endif decoding="async"
                                             class="size-full shrink-0 snap-center object-cover">
                                    @endforeach
                                </div>
                            @else
                                <div class="flex aspect-[2/1] short:aspect-[5/2] sm:aspect-[16/10] items-center justify-center lg:aspect-[4/3]" aria-hidden="true">
                                    <img src="{{ asset('images/brand/mofa-header-logo.png') }}" alt="" class="h-16 w-auto opacity-40">
                                </div>
                            @endif
                        </div>

                        @if ($item->badge)
                            {{-- A ribbon flag hanging from the top edge of the photo. --}}
                            <span class="absolute end-5 -top-1.5 z-10 flex w-[4.25rem] flex-col items-center bg-terracotta px-1.5 pt-4 pb-6 text-center text-[0.8125rem] leading-tight font-semibold text-on-terracotta shadow-soft [clip-path:polygon(0_0,100%_0,100%_100%,50%_calc(100%-0.875rem),0_100%)] lg:w-20 lg:text-sm">
                                {{ $item->badge->getLabel() }}
                            </span>
                            <span class="absolute end-[calc(1.25rem+4.25rem)] -top-1.5 z-10 h-1.5 w-1.5 bg-[#7d3a28] [clip-path:polygon(100%_0,100%_100%,0_100%)] rtl:[clip-path:polygon(0_0,100%_100%,0_100%)] lg:end-[calc(1.25rem+5rem)]" aria-hidden="true"></span>
                            <span class="absolute end-[calc(1.25rem-0.375rem)] -top-1.5 z-10 h-1.5 w-1.5 bg-[#7d3a28] [clip-path:polygon(0_0,100%_100%,0_100%)] rtl:[clip-path:polygon(100%_0,100%_100%,0_100%)]" aria-hidden="true"></span>
                        @endif

                        @if (count($images) > 1)
                            {{-- Dots on the photo, so they cost no height. --}}
                            <div class="absolute inset-x-0 bottom-2.5 flex justify-center gap-1.5">
                                @foreach ($images as $n => $src)
                                    <button type="button" @click="go({{ $n }})" class="flex size-6 items-center justify-center"
                                            aria-label="{{ __('Photo :n of :total', ['n' => $n + 1, 'total' => count($images)]) }}" :aria-current="i === {{ $n }}">
                                        <span class="h-2 rounded-pill bg-paper shadow-sm transition-all" :class="i === {{ $n }} ? 'w-5' : 'w-2 opacity-70'"></span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </figure>
                </div>

                {{-- ---------- The words ---------- --}}
                <div class="flex min-w-0 flex-col gap-4 lg:gap-6">
                    <header class="flex flex-col gap-1.5">
                        <h1 class="m-0 font-serif text-[2.25rem] short:text-[1.875rem] leading-[1] font-bold tracking-[0.01em] text-balance uppercase sm:text-5xl lg:text-[3.75rem] rtl:leading-[1.25] rtl:tracking-normal rtl:normal-case">{{ $name }}</h1>
                        @if ($tagline = $item->localized('tagline'))
                            <p class="m-0 self-start rtl:mt-1"><span class="brush text-[1.375rem] leading-none lg:text-[1.75rem]">{{ $tagline }}</span></p>
                        @endif
                    </header>

                    @if ($item->isOrderable())
                        <div class="hidden items-center gap-6 lg:flex">
                            <p class="m-0 font-serif text-[2.25rem] leading-none font-bold">{{ $item->formattedPrice() }}</p>
                            <x-menu.add-button :item="$item" size="large" class="flex-1" />
                        </div>
                    @endif

                    @include('pages.menu.details')
                </div>
            </div>
        </div>
    </article>

    @if ($more->isNotEmpty())
        <section class="bg-paper py-12 lg:py-16" aria-labelledby="more-title">
            <div class="mx-auto flex max-w-(--container-site) flex-col gap-6 lg:px-10">
                <h2 id="more-title" class="m-0 px-5 font-serif text-[1.75rem] font-bold lg:px-0">{{ __('More from :category', ['category' => $item->category->localized('name')]) }}</h2>
                {{-- Swipe on phones, a grid from lg up. --}}
                <div class="flex snap-x snap-mandatory gap-3 overflow-x-auto scroll-px-5 px-5 pb-2 [scrollbar-width:none] lg:grid lg:grid-cols-3 lg:gap-5 lg:overflow-visible lg:px-0 [&::-webkit-scrollbar]:hidden">
                    @foreach ($more as $other)
                        <x-menu.item-card :item="$other" class="w-[82%] shrink-0 snap-start bg-cream sm:w-[46%] lg:w-auto" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Phones and tablets: price and the order button, always in thumb reach. --}}
    @if ($item->isOrderable())
        <x-slot:bar>
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-hairline bg-paper/95 px-4 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] backdrop-blur lg:hidden">
            <div class="flex items-center gap-4">
                <div class="flex shrink-0 flex-col leading-tight">
                    <span class="max-w-[7rem] truncate text-xs text-ink-muted">{{ $name }}</span>
                    <span class="font-serif text-2xl font-bold">{{ $item->formattedPrice() }}</span>
                </div>
                <x-menu.add-button :item="$item" size="bar" class="min-w-0 flex-1" />
            </div>
        </div>
        </x-slot:bar>
    @endif
</x-layouts.app>
