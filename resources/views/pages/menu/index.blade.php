@php
    use App\Support\Settings;

    $cutoff = \Illuminate\Support\Carbon::createFromFormat('H:i', Settings::cutoff())->locale(app()->getLocale())->translatedFormat('g:i A');
    $total = $categories->sum(fn ($category) => $category->activeItems->count());
@endphp
<x-layouts.app :title="__('Menu')">
    {{-- ---------- Header ---------- --}}
    <section class="mx-auto flex max-w-(--container-site) flex-col gap-5 px-5 pt-4 pb-6 lg:flex-row lg:items-end lg:justify-between lg:px-10 lg:pt-10 lg:pb-10">
        <div class="flex flex-col gap-3">
            <x-section-title :script="__('Fresh every morning')" as="h1">{{ __('The menu') }}</x-section-title>
            <p class="m-0 max-w-[34rem] text-base leading-relaxed text-pretty text-ink-muted lg:max-w-[46rem] lg:text-lg">
                {{ __('Baked to order every morning. Order by :time and it reaches you tomorrow.', ['time' => $cutoff]) }}
            </p>
        </div>
        @if ($categories->isNotEmpty())
            <ul class="m-0 flex list-none flex-wrap gap-2 p-0 text-sm">
                <li class="flex items-center gap-2 rounded-pill bg-paper px-3.5 py-2"><x-mofa.icon name="clock" :size="16" /> {{ __('Order by :time', ['time' => $cutoff]) }}</li>
                <li class="flex items-center gap-2 rounded-pill bg-paper px-3.5 py-2"><x-mofa.icon name="leaf" :size="16" /> {{ __('Real sourdough, no shortcuts') }}</li>
            </ul>
        @endif
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
        <div x-data="menuFilter(@js($categories->pluck('slug')))">
            {{-- ---------- Filter: shows all, or one category. Sticks under the top while scrolling. ---------- --}}
            <nav class="sticky top-0 z-20 bg-cream/95 py-3 backdrop-blur" aria-label="{{ __('Categories') }}">
                <div class="mx-auto max-w-(--container-site) px-5 lg:px-10">
                    <div class="-mx-5 overflow-x-auto px-5 [scrollbar-width:none] lg:mx-0 lg:px-0 [&::-webkit-scrollbar]:hidden">
                        <div class="inline-flex gap-1 rounded-pill bg-paper p-1 shadow-[inset_0_0_0_1px_rgb(75_44_22/0.08)]" role="group">
                            @foreach ([['all', __('All'), $total], ...$categories->map(fn ($c) => [$c->slug, $c->localized('name'), $c->activeItems->count()])] as [$slug, $label, $count])
                                <a href="{{ $slug === 'all' ? route('menu') : '#'.$slug }}" @click.prevent="pick('{{ $slug }}')"
                                   :aria-current="active === '{{ $slug }}' ? 'true' : 'false'"
                                   :class="active === '{{ $slug }}' ? 'bg-brown text-paper shadow-sm' : 'text-brown hover:bg-cream/70'"
                                   class="flex min-h-10 shrink-0 items-center gap-2 rounded-pill px-4 text-[0.9375rem] font-medium whitespace-nowrap no-underline transition">
                                    {{ $label }}
                                    <span :class="active === '{{ $slug }}' ? 'bg-paper/20 text-paper' : 'bg-cream text-ink-muted'"
                                          class="min-w-6 rounded-pill px-1.5 py-0.5 text-center text-xs font-semibold leading-none">{{ $count }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </nav>

            {{-- ---------- The items, by category ---------- --}}
            <div x-ref="sections" class="mx-auto flex max-w-(--container-site) scroll-mt-20 flex-col gap-12 px-4 pt-4 pb-12 lg:gap-16 lg:px-10 lg:pt-6 lg:pb-20">
                @foreach ($categories as $category)
                    <section id="{{ $category->slug }}" x-show="shows('{{ $category->slug }}')" x-transition.opacity.duration.200ms
                             class="scroll-mt-24" aria-labelledby="cat-{{ $category->id }}">
                        <div class="mb-5 flex items-center gap-4 lg:mb-7">
                            <h2 id="cat-{{ $category->id }}" class="m-0 flex shrink-0 items-baseline gap-3 font-serif text-[1.75rem] leading-tight font-bold lg:text-[2.125rem]">
                                {{ $category->localized('name') }}
                                <span class="font-sans text-sm font-medium text-ink-muted">{{ trans_choice(':count item|:count items', $category->activeItems->count()) }}</span>
                            </h2>
                            <x-ornament class="max-w-none" />
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3 lg:gap-6">
                            @foreach ($category->activeItems as $item)
                                <x-menu.item-card :item="$item" />
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    @endif
</x-layouts.app>
