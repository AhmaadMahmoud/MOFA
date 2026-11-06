{{--
    Hero A: Faten inside the loaf arch.
    Mobile: headline, then the arch photo full-width with the script note beside it.
    Desktop: copy at the start, arch at the end (mirrors in Arabic).
--}}
<section class="relative isolate overflow-hidden" aria-labelledby="hero-title">
    <div class="mx-auto grid max-w-(--container-site) items-center gap-8 px-5 pt-2 pb-12 lg:grid-cols-[1.15fr_1fr] lg:gap-12 lg:px-10 lg:pt-10 lg:pb-24">
        {{-- Copy --}}
        <div class="flex flex-col items-start gap-4 lg:gap-6">
            <h1 id="hero-title" class="m-0 font-retro text-[3rem] leading-[0.95] font-normal tracking-[-0.01em] uppercase text-balance sm:text-7xl lg:text-[5.75rem] rtl:leading-[1.2] rtl:font-extrabold rtl:normal-case">
                {{ __('Your kind of morning.') }}
            </h1>

            <p class="m-0 hidden max-w-[30rem] text-lg leading-relaxed text-ink-muted lg:block">
                {{ __('Sourdough, bagels and layered sandwiches, baked fresh by Faten. Order today, wake up to it tomorrow.') }}
            </p>

            <div class="hidden gap-3 lg:flex">
                <x-button :href="route('menu')" class="h-14 px-8">{{ __('Order for tomorrow') }}</x-button>
                <x-button :href="route('menu')" variant="ghost" class="h-14 px-8">{{ __('See the menu') }}</x-button>
            </div>
        </div>

        {{-- Faten, cut out and standing straight on the page (the WebP is transparent; the JPG fallback is on cream). --}}
        <div class="relative mx-auto w-full max-w-[23rem] ps-[4.5rem] sm:max-w-[27rem] lg:max-w-[27rem] lg:ps-0">
            <figure class="relative m-0">
                <picture>
                    <source srcset="{{ asset('images/hero/faten.webp') }}?v={{ @filemtime(public_path('images/hero/faten.webp')) }}" type="image/webp">
                    <img src="{{ asset('images/hero/faten.jpg') }}?v={{ @filemtime(public_path('images/hero/faten.jpg')) }}" alt="{{ __('Faten, the founder of MOFA') }}" width="433" height="559"
                         fetchpriority="high" decoding="async"
                         class="block h-auto w-full">
                </picture>
            </figure>

            {{-- Script note + hand-drawn arrow toward the photo. --}}
            <div class="absolute start-0 top-10 flex w-[4.5rem] flex-col items-start gap-1 lg:-start-36 lg:top-6 lg:w-36" aria-hidden="true">
                <span class="-rotate-6 font-script text-[1.375rem] leading-none font-bold lg:text-[2.125rem]">{{ __('Made of Faten.') }}</span>
                <svg class="ms-3 h-12 w-14 lg:ms-14 lg:h-16 lg:w-24 rtl:-scale-x-100" viewBox="0 0 70 60" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 4 C10 30 30 46 62 48" /><path d="M52 40 L63 48 L52 55" />
                </svg>
            </div>
            <x-flower class="mofa-flower-spin absolute hidden text-terracotta lg:-start-24 lg:bottom-16 lg:block" />
        </div>

        <p class="m-0 text-base leading-relaxed text-ink-muted lg:hidden">
            {{ __('Sourdough, bagels and sandwiches, baked fresh. Order today for tomorrow morning.') }}
        </p>
    </div>
</section>
