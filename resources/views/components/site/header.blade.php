@props(['switchUrl' => null])
@php
    use App\Support\Locales;

    $other = Locales::other();
    $switchUrl ??= Locales::urlFor($other);

    // Faten (or any admin) signed in: a way back to the dashboard. Nobody else sees it.
    $dashboardUrl = auth()->user()?->canAccessPanel(filament()->getPanel('admin'))
        ? filament()->getPanel('admin')->getUrl()
        : null;

    $links = [
        ['label' => __('Menu'), 'href' => route('menu')],
        ['label' => __('The MOFA Way'), 'href' => route('home').'#story'],
        ['label' => __('How to order'), 'href' => route('home').'#how-to-order'],
    ];
@endphp

<header x-data="{ open: false }" @keydown.escape.window="open = false" class="relative z-40">
    <div class="mx-auto flex h-16 max-w-(--container-site) items-center justify-between gap-4 ps-5 pe-3 lg:h-24 lg:px-10">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center" aria-label="{{ __('MOFA home') }}">
            <img src="{{ asset('images/brand/mofa-header-logo.png') }}" alt="MOFA, Made of Faten" width="331" height="169" class="h-[3.375rem] w-auto lg:h-[4.75rem]">
        </a>

        <nav class="hidden items-center gap-10 lg:flex" aria-label="{{ __('Main') }}">
            @foreach ($links as $link)
                <a href="{{ $link['href'] }}" class="eyebrow text-sm tracking-[0.18em] text-brown no-underline hover:text-ink-muted">{{ $link['label'] }}</a>
            @endforeach
        </nav>

        <div class="flex items-center gap-1 lg:gap-4">
            {{-- Short code (AR / EN); the full language name is read out to screen readers. --}}
            <a href="{{ $switchUrl }}" hreflang="{{ $other }}" aria-label="{{ $other === 'ar' ? 'العربية' : 'English' }}"
               class="relative flex h-11 min-w-11 items-center justify-center px-3 text-base font-semibold tracking-[0.08em] text-brown no-underline after:absolute after:inset-x-3 after:bottom-0.5 after:h-0.5 after:origin-center after:scale-x-0 after:rounded-pill after:bg-brown/50 after:transition-transform after:duration-200 after:content-[''] hover:after:scale-x-100">
                <span dir="ltr">{{ strtoupper($other) }}</span>
            </a>

            @if ($dashboardUrl)
                {{-- Icon only, like the bag: same size, same hover. --}}
                <a href="{{ $dashboardUrl }}" class="flex size-11 items-center justify-center rounded-pill text-brown no-underline transition-colors hover:text-ink-muted"
                   aria-label="{{ __('Dashboard') }}" title="{{ __('Dashboard') }}">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="4" width="7" height="7" rx="1.5" /><rect x="13" y="4" width="7" height="7" rx="1.5" /><rect x="4" y="13" width="7" height="7" rx="1.5" /><rect x="13" y="13" width="7" height="7" rx="1.5" /></svg>
                </a>
            @endif

            <x-button :href="route('menu')" class="hidden lg:inline-flex">{{ __('Order now') }}</x-button>

            {{-- Cart: always reachable, with the count. --}}
            <button type="button" x-data @click="$store.cart.open = true"
                    class="relative flex size-11 items-center justify-center rounded-pill text-brown transition-colors hover:text-ink-muted"
                    :aria-label="@js(__('Your order')) + ($store.cart.count ? ' (' + $store.cart.count + ')' : '')" aria-label="{{ __('Your order') }}">
                <x-mofa.icon name="bag" />
                <span x-cloak x-show="$store.cart.count > 0" x-text="$store.cart.count"
                      class="absolute end-0.5 top-0.5 flex h-5 min-w-5 items-center justify-center rounded-pill bg-terracotta px-1 text-[0.6875rem] leading-none font-semibold text-on-terracotta"></span>
            </button>

            <button type="button" class="flex size-11 items-center justify-center rounded-pill text-brown transition-colors hover:text-ink-muted lg:hidden"
                    @click="open = true" :aria-expanded="open.toString()" aria-controls="mobile-nav" aria-label="{{ __('Open menu') }}">
                <x-mofa.icon name="menu" />
            </button>
        </div>
    </div>

    {{-- Mobile navigation: a full-height sheet from the inline end. --}}
    <div id="mobile-nav" x-cloak x-show="open" class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="{{ __('Menu') }}">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-brown/40" @click="open = false"></div>
        <div x-show="open" x-trap.noscroll="open"
             x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 translate-x-6 rtl:-translate-x-6" x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="absolute inset-y-0 end-0 flex w-[min(22rem,88vw)] flex-col bg-paper pb-[env(safe-area-inset-bottom)]">
            <div class="flex h-16 items-center justify-between ps-5 pe-3">
                <img src="{{ asset('images/brand/mofa-header-logo.png') }}" alt="" width="331" height="169" class="h-[3.375rem] w-auto">
                <button type="button" class="flex size-11 items-center justify-center rounded-pill text-brown hover:bg-cream" @click="open = false" aria-label="{{ __('Close menu') }}">
                    <x-mofa.icon name="close" />
                </button>
            </div>
            <nav class="flex flex-col px-5 pt-4" aria-label="{{ __('Main') }}">
                @foreach ($links as $link)
                    <a href="{{ $link['href'] }}" @click="open = false" class="flex min-h-14 items-center border-b border-hairline font-serif text-[1.625rem] font-bold text-brown no-underline">{{ $link['label'] }}</a>
                @endforeach
            </nav>
            <div class="mt-auto flex flex-col gap-3 p-5">
                @if ($dashboardUrl)
                    <x-button :href="$dashboardUrl" variant="ghost" class="w-full">{{ __('Go to the dashboard') }}</x-button>
                @endif
                <x-button :href="route('menu')" class="w-full">{{ __('Order for tomorrow') }}</x-button>
                <a href="https://www.instagram.com/mofa20261/" target="_blank" rel="noopener" class="flex min-h-12 items-center justify-center gap-2 text-ink-muted no-underline">
                    <x-mofa.icon name="instagram" :size="20" /> <span dir="ltr">@mofa20261</span>
                </a>
            </div>
        </div>
    </div>
</header>
