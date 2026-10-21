<x-layouts.app :title="__('Menu')">
    <section class="mx-auto max-w-(--container-site) px-5 pt-4 pb-6 lg:px-10 lg:pt-10 lg:pb-10">
        <x-section-title :script="__('Fresh every morning')" as="h1">{{ __('The menu') }}</x-section-title>
    </section>

    @if ($categories->isEmpty())
        <section class="mx-auto max-w-(--container-site) px-4 pb-16 lg:px-10">
            <x-wave-frame>
                <div class="mx-auto flex max-w-[34rem] flex-col items-center gap-6 text-center">
                    <p class="m-0 font-script text-3xl font-bold">{{ __('Something new is coming…') }}</p>
                    <p class="m-0 text-lg leading-relaxed">{{ __('The full menu is on its way. Until then, see what is fresh on our Instagram.') }}</p>
                    <x-button href="https://www.instagram.com/mofa20261/" variant="ghost" target="_blank" rel="noopener" class="normal-case">
                        <x-mofa.icon name="instagram" :size="20" /> <span dir="ltr">@mofa20261</span>
                    </x-button>
                </div>
            </x-wave-frame>
        </section>
    @else
        {{-- Category bar: sticks under the top while scrolling, highlights the section in view. --}}
        <nav x-data="categoryBar" class="sticky top-0 z-20 border-y border-hairline bg-cream/95 backdrop-blur" aria-label="{{ __('Categories') }}">
            <ul x-ref="list" class="mx-auto m-0 flex max-w-(--container-site) list-none gap-2 overflow-x-auto px-5 py-3 [scrollbar-width:none] lg:px-10 [&::-webkit-scrollbar]:hidden">
                @foreach ($categories as $category)
                    <li class="shrink-0">
                        <a href="#{{ $category->slug }}" data-target="{{ $category->slug }}"
                           :class="active === '{{ $category->slug }}' ? 'bg-brown text-paper border-brown' : 'bg-transparent text-brown border-brown/40'"
                           class="flex min-h-11 items-center rounded-pill border px-4 text-[0.9375rem] font-medium whitespace-nowrap no-underline transition">
                            {{ $category->localized('name') }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="mx-auto flex max-w-(--container-site) flex-col gap-12 px-4 py-8 lg:gap-16 lg:px-10 lg:py-12">
            @foreach ($categories as $category)
                <section id="{{ $category->slug }}" data-category class="scroll-mt-20" aria-labelledby="cat-{{ $category->id }}">
                    <div class="mb-5 flex items-center gap-4">
                        <h2 id="cat-{{ $category->id }}" class="m-0 shrink-0 font-serif text-[1.75rem] font-bold leading-tight lg:text-[2.125rem]">{{ $category->localized('name') }}</h2>
                        <x-ornament class="max-w-none" />
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3">
                        @foreach ($category->activeItems as $item)
                            <x-menu.item-card :item="$item" />
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</x-layouts.app>
