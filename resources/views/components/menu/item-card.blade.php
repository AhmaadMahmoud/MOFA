@props(['item'])
{{-- Mobile: photo beside the text, like a menu line. sm and up: photo on top.
     The whole card opens the item (stretched link); the add button sits above it. --}}
<article {{ $attributes->class('group relative flex gap-4 rounded-md bg-paper p-3 text-brown transition hover:shadow-soft focus-within:shadow-soft sm:flex-col sm:gap-0 sm:p-0 sm:overflow-hidden') }}>
    <div class="relative aspect-square w-28 shrink-0 overflow-hidden rounded-[0.625rem] bg-cream sm:aspect-[4/3] sm:w-full sm:rounded-none">
        @if ($cover = $item->coverUrl())
            <img src="{{ $cover }}" alt="" loading="lazy" decoding="async" class="size-full object-cover transition duration-300 group-hover:scale-[1.03]">
        @else
            <div class="flex size-full items-center justify-center text-brown/35" aria-hidden="true">
                <img src="{{ asset('images/brand/apple-touch-icon.png') }}" alt="" class="size-14 rounded-full opacity-60 mix-blend-multiply">
            </div>
        @endif
        @if ($item->badge)
            <span class="absolute start-2 top-2 rounded-pill bg-terracotta px-2.5 py-1 text-[0.6875rem] font-medium tracking-[0.12em] text-on-terracotta uppercase rtl:tracking-normal rtl:normal-case">{{ $item->badge->getLabel() }}</span>
        @endif
    </div>

    <div class="flex min-w-0 flex-1 flex-col gap-1 py-1 sm:p-5">
        <h3 class="m-0 font-serif text-[1.375rem] leading-tight font-bold sm:text-2xl">
            <a href="{{ route('menu.item', ['item' => $item]) }}" class="text-brown no-underline after:absolute after:inset-0 after:rounded-md focus-visible:outline-none">{{ $item->localized('name') }}</a>
        </h3>
        @if ($tagline = $item->localized('tagline'))
            <p class="m-0 font-serif text-base italic leading-snug text-ink-muted">{{ $tagline }}</p>
        @endif
        @if ($description = $item->localized('description'))
            <p class="m-0 line-clamp-2 text-sm leading-relaxed text-ink-muted">{{ $description }}</p>
        @endif
        <div class="mt-auto flex items-center justify-between gap-3 pt-2">
            <div class="flex flex-col">
                <span class="font-serif text-xl font-bold">{{ $item->formattedPrice() }}</span>
                @if ($item->calories)
                    <span class="text-xs text-ink-muted">{{ __(':n kcal', ['n' => $item->calories]) }}</span>
                @endif
            </div>
            <x-menu.add-button :item="$item" />
        </div>
    </div>
</article>
