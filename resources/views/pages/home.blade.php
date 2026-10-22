<x-layouts.app>
    <x-site.hero />

    {{-- The three promises printed under the logo on packaging and posts. --}}
    <section class="border-y border-hairline bg-paper" aria-label="{{ __('Our promises') }}">
        <ul class="mx-auto m-0 grid max-w-(--container-site) list-none grid-cols-3 p-0 px-2 py-7 lg:px-10 lg:py-9">
            @foreach ([['leaf', __('Real ingredients')], ['no-oil', __('No hydrogenated oils')], ['heart', __('Made with care')]] as [$icon, $label])
                <li class="flex flex-col items-center gap-3 px-2 text-center not-first:border-s not-first:border-hairline">
                    <span class="flex size-12 items-center justify-center rounded-full border border-brown lg:size-14">
                        <x-mofa.icon :name="$icon" :size="22" />
                    </span>
                    <span class="eyebrow text-[0.6875rem] tracking-[0.16em] leading-snug sm:text-eyebrow">{{ $label }}</span>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Featured items, chosen in the dashboard. --}}
    @if (($featured ?? collect())->isNotEmpty())
        <section class="mx-auto max-w-(--container-site) px-4 pt-16 lg:px-10 lg:pt-24" aria-labelledby="featured-title">
            <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
                <x-section-title :script="__('Fresh every morning')">
                    <span id="featured-title">{{ __('From the menu') }}</span>
                </x-section-title>
                <a href="{{ route('menu') }}" class="inline-flex min-h-11 items-center gap-2 font-medium text-brown underline-offset-4 hover:underline">
                    {{ __('See the full menu') }} <x-mofa.icon name="arrow" :size="18" class="flip-rtl" />
                </a>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3">
                @foreach ($featured as $item)
                    <x-menu.item-card :item="$item" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Story teaser --}}
    <section id="story" class="mx-auto max-w-(--container-site) scroll-mt-6 px-4 py-16 lg:px-10 lg:py-24">
        <x-wave-frame>
            <div class="mx-auto flex max-w-[38rem] flex-col items-center gap-6 text-center">
                <x-section-title :script="__('Let me take you back a little…')" retro align="center" class="[&_h2]:text-[2.25rem] sm:[&_h2]:text-[3.25rem]">
                    {{ __('What does MOFA even mean?') }}
                </x-section-title>
                <x-ornament />
                <p class="m-0 text-lg leading-relaxed">
                    {{ __('MOFA stands for Made Of Faten. Sourdough, bagels and layered sandwiches, made with real ingredients, no hydrogenated oils, and a lot of care.') }}
                </p>
            </div>
        </x-wave-frame>
    </section>

    {{-- How ordering works --}}
    <section id="how-to-order" class="scroll-mt-6 bg-paper py-16 lg:py-24" aria-labelledby="how-title">
        <div class="mx-auto flex max-w-(--container-site) flex-col items-center gap-10 px-5 lg:px-10">
            <x-section-title :script="__('Easy as one, two, three')" align="center">
                <span id="how-title">{{ __('How to order') }}</span>
            </x-section-title>

            <ol class="m-0 grid w-full list-none gap-4 p-0 md:grid-cols-3 md:gap-6">
                @foreach ([
                    ['basket', __('Pick from the menu'), __('Sandwiches, bagels and ready-made boxes.')],
                    ['calendar', __('Choose your morning'), __('Order today for tomorrow, or book a few days ahead.')],
                    ['phone-pay', __('Pay with InstaPay'), __('Transfer with InstaPay, send us the receipt on WhatsApp, and we confirm your order.')],
                ] as $i => [$icon, $title, $text])
                    <li class="flex items-start gap-4 rounded-md bg-cream p-5 md:flex-col md:p-7">
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-brown text-paper">
                            <x-mofa.icon :name="$icon" :size="22" />
                        </span>
                        <div class="flex flex-col gap-1">
                            <span class="font-script text-xl font-bold text-brown">{{ __('Step :n', ['n' => $i + 1]) }}</span>
                            <h3 class="m-0 font-serif text-2xl font-bold leading-tight">{{ $title }}</h3>
                            <p class="m-0 text-[0.9375rem] leading-relaxed text-ink-muted">{{ $text }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>

            <x-button :href="route('menu')" class="hidden h-14 px-8 lg:inline-flex">{{ __('Order for tomorrow') }}</x-button>
        </div>
    </section>
</x-layouts.app>
