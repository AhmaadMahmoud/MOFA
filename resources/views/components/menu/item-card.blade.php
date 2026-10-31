@props(['item'])
{{-- Mobile: photo beside the text, like a menu line. sm and up: a framed photo on top.
     The whole card opens the item (stretched link); the add button sits above it. --}}
<article {{ $attributes->class('group relative flex gap-4 rounded-[1.25rem] bg-paper p-2.5 text-brown shadow-[0_1px_0_rgb(75_44_22/0.06)] transition duration-300 hover:-translate-y-0.5 hover:shadow-soft focus-within:shadow-soft sm:flex-col sm:gap-0 motion-reduce:hover:translate-y-0') }}>
    <div class="relative aspect-square w-28 shrink-0 overflow-hidden rounded-[0.875rem] bg-cream sm:aspect-[4/3] sm:w-full">
        @if ($cover = $item->coverUrl())
            <img src="{{ $cover }}" alt="" loading="lazy" decoding="async" class="size-full object-cover transition duration-500 group-hover:scale-[1.04] motion-reduce:group-hover:scale-100">
        @else
            <div class="flex size-full items-center justify-center text-brown/35" aria-hidden="true">
                <img src="{{ asset('images/brand/apple-touch-icon.png') }}" alt="" class="size-14 rounded-full opacity-60 mix-blend-multiply">
            </div>
        @endif
        @if ($item->badge)
            <span class="absolute start-2 top-2 rounded-pill bg-terracotta px-2.5 py-1 text-[0.6875rem] font-medium tracking-[0.12em] text-on-terracotta uppercase sm:start-3 sm:top-3 rtl:tracking-normal rtl:normal-case">{{ $item->badge->getLabel() }}</span>
        @endif
    </div>

    <div class="flex min-w-0 flex-1 flex-col gap-1 py-1 pe-1 sm:px-2.5 sm:pt-4 sm:pb-2">
        <h3 class="m-0 font-serif text-[1.375rem] leading-tight font-bold sm:text-[1.625rem]">
            <a href="{{ route('menu.item', ['item' => $item]) }}" class="text-brown no-underline after:absolute after:inset-0 after:rounded-[1.25rem] focus-visible:outline-none">{{ $item->localized('name') }}</a>
        </h3>
        @if ($tagline = $item->localized('tagline'))
            <p class="m-0 font-serif text-base leading-snug text-ink-muted italic rtl:not-italic">{{ $tagline }}</p>
        @endif
        @if ($description = $item->localized('description'))
            <p class="m-0 mt-1 line-clamp-2 text-sm leading-relaxed text-ink-muted">{{ $description }}</p>
        @endif

        <div class="mt-auto flex items-center justify-between gap-3 pt-3 sm:mt-3 sm:border-t sm:border-dashed sm:border-brown/15 sm:pt-4">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:gap-2.5">
                <span class="font-serif text-[1.375rem] leading-none font-bold whitespace-nowrap">{{ $item->formattedPrice() }}</span>
                @if ($item->calories)
                    <span class="text-xs whitespace-nowrap text-ink-muted">{{ __(':n kcal', ['n' => $item->calories]) }}</span>
                @endif
            </div>
            <x-menu.add-button :item="$item" />
        </div>
    </div>
</article>
