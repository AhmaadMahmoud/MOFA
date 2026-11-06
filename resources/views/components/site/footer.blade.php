@php
    use App\Support\Settings;

    $cutoff = \Illuminate\Support\Carbon::createFromFormat('H:i', Settings::cutoff())->locale(app()->getLocale())->translatedFormat('g:i A');
    $whatsapp = Settings::whatsappLink(__('Hello MOFA, I have a question.'));
    $heading = 'm-0 mb-4 text-xs font-semibold tracking-[0.2em] text-paper/55 uppercase rtl:tracking-normal rtl:text-sm';
    $link = 'flex min-h-10 items-center gap-2.5 text-paper/90 no-underline transition hover:text-paper';
@endphp
<footer class="relative isolate overflow-hidden bg-brown text-paper">
    <div class="mx-auto max-w-(--container-site) px-5 lg:px-10">
        {{-- ---------- Four columns ---------- --}}
        <div class="grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-[1.3fr_1fr_1.2fr_1.2fr] lg:gap-12 lg:py-14">
            <div class="flex flex-col items-start gap-4">
                {{-- The logo drawn in paper on the brown, no badge. --}}
                <a href="{{ route('home') }}" aria-label="{{ __('MOFA home') }}">
                    <img src="{{ asset('images/brand/mofa-header-logo.png') }}" alt="MOFA, Made of Faten" width="331" height="169" class="h-20 w-auto [filter:brightness(0)_invert(1)_sepia(0.25)]">
                </a>
                <p class="m-0 max-w-[18rem] text-sm leading-relaxed text-paper/70">
                    {{ __('Sourdough, bagels and layered sandwiches, baked fresh by Faten. Order today for tomorrow morning.') }}
                </p>
            </div>

            <nav aria-label="{{ __('Footer') }}">
                <p class="{{ $heading }}">{{ __('Explore') }}</p>
                <a href="{{ route('menu') }}" class="{{ $link }}">{{ __('Menu') }}</a>
                <a href="{{ route('home') }}#story" class="{{ $link }}">{{ __('The MOFA Way') }}</a>
                <a href="{{ route('home') }}#how-to-order" class="{{ $link }}">{{ __('How to order') }}</a>
            </nav>

            <div>
                <p class="{{ $heading }}">{{ __('Ordering') }}</p>
                <ul class="m-0 flex list-none flex-col gap-1 p-0">
                    <li class="{{ $link }}"><x-mofa.icon name="clock" :size="18" class="shrink-0 text-cream" /> {{ __('Order by :time', ['time' => $cutoff]) }}</li>
                    <li class="{{ $link }}"><x-mofa.icon name="calendar" :size="18" class="shrink-0 text-cream" /> {{ __('Delivered the next morning') }}</li>
                    <li class="{{ $link }}"><x-mofa.icon name="phone-pay" :size="18" class="shrink-0 text-cream" /> {{ __('Pay with InstaPay') }}</li>
                </ul>
            </div>

            <div>
                <p class="{{ $heading }}">{{ __('Get in touch') }}</p>
                @if ($whatsapp)
                    <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="{{ $link }}"><x-mofa.icon name="chat" :size="18" class="shrink-0 text-cream" /> {{ __('WhatsApp') }}</a>
                @endif
                <a href="https://www.instagram.com/mofa20261/" target="_blank" rel="noopener" class="{{ $link }}"><x-mofa.icon name="instagram" :size="18" class="shrink-0 text-cream" /> <span dir="ltr">@mofa20261</span></a>
                <a href="mailto:mofa20261@outlook.com" class="{{ $link }}"><x-mofa.icon name="mail" :size="18" class="shrink-0 text-cream" /> <span dir="ltr" class="break-all">mofa20261@outlook.com</span></a>
            </div>
        </div>
    </div>

    {{-- ---------- Bottom line ---------- --}}
    <div class="border-t border-paper/15">
        <div class="mx-auto flex max-w-(--container-site) flex-col items-center justify-between gap-2 px-5 py-5 text-sm text-paper/60 sm:flex-row lg:px-10">
            <span>© {{ now()->year }} MOFA · {{ __('Made of Faten') }}</span>
            <span class="flex items-center gap-2"><x-flower class="size-4 text-terracotta" /> {{ __('Est. 2026') }}</span>
        </div>
    </div>
</footer>
