@props(['category'])
@php
    // Cover: the category photo from the dashboard, or the first item's photo.
    // The small round photos are the items inside (skipping the one already used as the cover).
    $items = $category->activeItems;
    $cover = $category->coverUrl();
    $thumbs = $items->map(fn ($item) => $item->coverUrl())->filter()->reject(fn ($url) => $url === $cover)->take(3);
@endphp
{{-- A paper mat around the photo, like a framed print. --}}
<a href="{{ route('menu') }}#{{ $category->slug }}"
   {{ $attributes->class('group block rounded-[1.625rem] bg-paper p-1 text-paper no-underline shadow-[0_1px_0_rgb(75_44_22/0.06),0_10px_30px_-18px_rgb(75_44_22/0.45)] transition duration-300 hover:-translate-y-1 hover:shadow-soft focus-visible:outline-offset-4 sm:p-1.5 motion-reduce:hover:translate-y-0') }}>
    <span class="relative isolate flex aspect-[5/4] flex-col justify-end overflow-hidden rounded-[1.375rem] bg-brown sm:aspect-[4/3]">
        @if ($cover)
            <img src="{{ $cover }}" alt="" loading="lazy" decoding="async"
                 class="absolute inset-0 -z-10 size-full object-cover transition duration-700 group-hover:scale-[1.05] motion-reduce:group-hover:scale-100">
        @endif
        {{-- Brown fade from the bottom, so the name reads on any photo. --}}
        <span class="absolute inset-0 -z-10 bg-gradient-to-t from-brown via-brown/45 to-transparent" aria-hidden="true"></span>

        <span class="flex items-end justify-between gap-4 p-5 sm:p-7">
            <span class="flex min-w-0 flex-col gap-1.5">
                <span class="text-sm font-medium text-paper/80">{{ trans_choice(':count item|:count items', $items->count()) }}</span>
                <span class="font-serif text-[2rem] leading-[1.05] font-bold text-balance sm:text-[2.375rem]">{{ $category->localized('name') }}</span>
                @if ($thumbs->isNotEmpty())
                    <span class="mt-2 flex -space-x-2.5 rtl:space-x-reverse" aria-hidden="true">
                        @foreach ($thumbs as $thumb)
                            <img src="{{ $thumb }}" alt="" loading="lazy" class="size-10 rounded-full border-2 border-paper object-cover">
                        @endforeach
                    </span>
                @endif
            </span>
            <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-paper text-brown transition duration-300 group-hover:bg-terracotta group-hover:text-on-terracotta">
                <x-mofa.icon name="arrow" :size="20" class="flip-rtl" />
            </span>
        </span>
    </span>
</a>
