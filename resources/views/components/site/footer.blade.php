<footer class="bg-brown text-paper">
    <div class="mx-auto flex max-w-(--container-site) flex-col items-center gap-8 px-5 py-14 text-center lg:flex-row lg:items-start lg:justify-between lg:px-10 lg:text-start">
        <div class="flex flex-col items-center gap-3 lg:items-start">
            {{-- The logo's ink is brown, so it sits on a paper badge on the dark footer. --}}
            <a href="{{ route('home') }}" class="rounded-md bg-paper px-5 py-4" aria-label="{{ __('MOFA home') }}">
                <img src="{{ asset('images/brand/mofa-header-logo.png') }}" alt="MOFA, Made of Faten" width="331" height="169" class="h-16 w-auto">
            </a>
            <p class="m-0 font-script text-2xl font-bold">{{ __('Your kind of morning.') }}</p>
        </div>

        <nav class="flex flex-col gap-1" aria-label="{{ __('Footer') }}">
            <a href="{{ route('menu') }}" class="min-h-11 content-center text-paper no-underline hover:underline">{{ __('Menu') }}</a>
            <a href="{{ route('home') }}#story" class="min-h-11 content-center text-paper no-underline hover:underline">{{ __('The MOFA Way') }}</a>
            <a href="{{ route('home') }}#how-to-order" class="min-h-11 content-center text-paper no-underline hover:underline">{{ __('How to order') }}</a>
        </nav>

        <div class="flex flex-col items-center gap-1 lg:items-start">
            <a href="https://www.instagram.com/mofa20261/" target="_blank" rel="noopener" class="flex min-h-11 items-center gap-2 text-paper no-underline hover:underline">
                <x-mofa.icon name="instagram" :size="20" /> <span dir="ltr">@mofa20261</span>
            </a>
            <a href="mailto:mofa20261@outlook.com" class="flex min-h-11 items-center gap-2 text-paper no-underline hover:underline">
                <x-mofa.icon name="mail" :size="20" /> <span dir="ltr">mofa20261@outlook.com</span>
            </a>
        </div>
    </div>
    <div class="border-t border-paper/15 px-5 py-5 text-center text-sm text-paper/80">
        © {{ now()->year }} MOFA · {{ __('Made of Faten') }} · {{ __('Est. 2026') }}
    </div>
</footer>
