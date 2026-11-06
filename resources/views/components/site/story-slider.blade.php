{{--
    "The MOFA way": the four story posts as a slider that moves by itself.
    Pure type and hand-drawn doodles (no post images). Slides share one grid cell,
    so the panel is as tall as the tallest slide and never jumps.
    Motion lives in app.css (.story-*): words rise in, lines draw themselves, the stamp turns.
--}}
@php
    $slides = [
        ['key' => 'choices', 'tab' => __('3 choices')],
        ['key' => 'different', 'tab' => __('What makes us different')],
        ['key' => 'honest', 'tab' => __('Simple & honest')],
        ['key' => 'mission', 'tab' => __('The mission')],
        ['key' => 'vision', 'tab' => __('The vision')],
    ];
    $count = count($slides);

    // Slide 3 shows one of our sandwiches: the first "Featured on the home page" item with a photo,
    // else the first item with a photo. Faten picks it from the dashboard.
    $withPhoto = fn () => \App\Models\Item::query()->active()->whereNotNull('images')->ordered()->get()->first(fn ($item) => $item->coverUrl());
    $sandwich = \App\Models\Item::query()->active()->where('is_featured', true)->ordered()->get()->first(fn ($item) => $item->coverUrl()) ?? $withPhoto();

    // A five-petal doodle flower, like the posts.
    $flower = fn (string $class = '') => '<svg class="'.$class.'" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round" aria-hidden="true">'
        .collect(range(0, 4))->map(fn ($i) => '<ellipse cx="24" cy="13" rx="6.5" ry="10" transform="rotate('.($i * 72).' 24 24)" />')->implode('')
        .'<circle cx="24" cy="24" r="3.2" fill="currentColor" /></svg>';
@endphp

<section id="story" class="mx-auto max-w-(--container-site) scroll-mt-6 px-4 py-16 lg:px-10 lg:py-24"
         x-data="storySlider({{ $count }}, 7)"
         aria-roledescription="carousel" aria-labelledby="story-title"
         @keydown.arrow-right.prevent="document.dir === 'rtl' ? prev() : next()"
         @keydown.arrow-left.prevent="document.dir === 'rtl' ? next() : prev()">

    {{-- Heading, the slide number and the controls --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-5 lg:mb-8">
        <x-section-title :script="__('Let me take you back a little…')">
            <span id="story-title">{{ __('The MOFA Way') }}</span>
        </x-section-title>

        <div class="flex items-center">
            <p class="m-0 flex items-baseline gap-1 font-retro text-brown rtl:font-extrabold" aria-hidden="true" dir="ltr">
                <span class="text-[2.25rem] leading-none" x-text="String(active + 1).padStart(2, '0')">01</span>
                <span class="text-lg text-ink-muted">/ {{ str_pad((string) $count, 2, '0', STR_PAD_LEFT) }}</span>
            </p>
        </div>
    </div>

    {{-- The panel: the same paper frame as the cards --}}
    <div class="relative rounded-[2rem] bg-paper p-1 shadow-[0_1px_0_rgb(75_44_22/0.06),0_18px_40px_-24px_rgb(75_44_22/0.5)] sm:p-1.5"
         @mouseenter="hovering = true" @mouseleave="hovering = false"
         @focusin="hovering = true" @focusout="hovering = false"
         @touchstart.passive="touchStart($event)" @touchend="touchEnd($event)">
        <div class="mofa-grain relative isolate grid overflow-hidden rounded-[1.75rem] [grid-template-areas:'stack']">

            {{-- ===== 1. Three choices, twenty a day ===== --}}
            <article class="story-slide bg-[#f3e6d6]" :class="active === 0 && 'is-active'" :inert="active !== 0"
                     role="group" aria-roledescription="slide" aria-label="{{ __('Slide :n of :total', ['n' => 1, 'total' => $count]) }}">
                <svg class="story-blob absolute -end-24 -top-28 -z-10 h-[26rem] w-[26rem] text-terracotta/15 lg:-end-16 lg:-top-24 lg:h-[34rem] lg:w-[34rem]" viewBox="0 0 200 200" aria-hidden="true">
                    <path fill="currentColor" d="M44 -60C58 -48 70 -30 74 -10C78 10 74 32 60 46C46 60 22 66 0 66C-22 66 -44 60 -58 44C-72 28 -78 4 -72 -18C-66 -40 -48 -58 -28 -68C-8 -78 30 -72 44 -60Z" transform="translate(100 100)" />
                </svg>

                <div class="grid items-center gap-10 lg:grid-cols-[1.1fr_1fr] lg:gap-14">
                    <div class="flex flex-col items-start gap-6">
                        <h3 class="story-in story-retro relative m-0 -rotate-3 text-[3.25rem] sm:text-[4.5rem] lg:text-[5.25rem]" style="--d:80ms">
                            <span class="block">{{ __('3 choices.') }}</span>
                            <span class="block ps-[0.4em] rtl:ps-0">{{ __('20 a day.') }}</span>
                            {!! $flower('story-spin absolute -end-10 -top-4 size-11 text-brown sm:-end-14 sm:size-14') !!}
                        </h3>
                        <a href="{{ route('menu') }}" class="story-in group/cta inline-flex items-center gap-3 border-b-2 border-brown pb-1.5 text-sm font-semibold tracking-[0.14em] text-brown uppercase sm:tracking-[0.22em] no-underline rtl:tracking-normal rtl:normal-case rtl:text-base" style="--d:520ms">
                            {{ __('Order before they\'re gone') }}
                            <svg class="size-5 transition-transform duration-300 group-hover/cta:-translate-y-0.5 group-hover/cta:translate-x-1 rtl:-scale-x-100 rtl:group-hover/cta:-translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M21 3 3 10.5l7.5 3L13.5 21z" /><path d="m10.5 13.5 4.5-4.5" /></svg>
                        </a>
                    </div>

                    <ul class="m-0 grid list-none grid-cols-3 p-0">
                        @foreach ([
                            ['loaf', __('Fresh sourdough.')],
                            ['sun', __('Made every morning.')],
                            ['twenty', __('Only 20 sandwiches a day.')],
                        ] as $i => [$icon, $label])
                            <li class="story-in flex flex-col items-center gap-4 px-2 text-center not-first:border-s-[1.5px] not-first:border-brown/70 sm:px-4" style="--d:{{ 260 + $i * 140 }}ms">
                                <span class="flex h-16 items-center text-brown sm:h-20">
                                    @if ($icon === 'loaf')
                                        <svg class="story-draw h-12 w-20 sm:h-14 sm:w-24" viewBox="0 0 96 52" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true">
                                            <path pathLength="1" d="M10 46C3 46 1 40 4 33 10 17 28 7 48 7s38 10 44 26c3 7 1 13-6 13Z" />
                                            <path pathLength="1" d="M30 30c4-8 9-13 15-16M44 32c4-8 9-13 15-16M58 33c4-7 8-11 13-14" />
                                        </svg>
                                    @elseif ($icon === 'sun')
                                        <svg class="story-draw story-spin-slow size-16 sm:size-20" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true">
                                            <circle pathLength="1" cx="32" cy="32" r="11" />
                                            <path pathLength="1" d="M32 6v8M32 50v8M6 32h8M50 32h8M13.6 13.6l5.6 5.6M44.8 44.8l5.6 5.6M13.6 50.4l5.6-5.6M44.8 19.2l5.6-5.6" />
                                        </svg>
                                    @else
                                        <span class="relative flex size-16 items-center justify-center sm:size-20">
                                            <svg class="story-draw absolute inset-0 size-full" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><circle pathLength="1" cx="32" cy="32" r="29" /></svg>
                                            <span class="font-sans text-2xl font-semibold sm:text-[1.75rem]" dir="ltr">20</span>
                                        </span>
                                    @endif
                                </span>
                                <span class="story-script text-[1.25rem] leading-tight sm:text-[1.625rem]">{{ $label }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </article>

            {{-- ===== 2. What makes MOFA different ===== --}}
            <article class="story-slide bg-[#efe6d3]" :class="active === 1 && 'is-active'" :inert="active !== 1"
                     role="group" aria-roledescription="slide" aria-label="{{ __('Slide :n of :total', ['n' => 2, 'total' => $count]) }}">
                <svg class="story-blob absolute -bottom-32 -start-28 -z-10 h-[26rem] w-[26rem] text-olive/20 lg:h-[34rem] lg:w-[34rem]" viewBox="0 0 200 200" aria-hidden="true">
                    <path fill="currentColor" d="M52 -62C66 -50 74 -30 76 -10C78 10 72 30 58 46C44 62 22 72 0 72C-22 72 -42 60 -56 44C-70 28 -78 6 -74 -14C-70 -34 -54 -52 -34 -64C-14 -76 38 -74 52 -62Z" transform="translate(100 100)" />
                </svg>

                <div class="grid items-center gap-10 lg:grid-cols-[1.05fr_1fr] lg:gap-14">
                    <h3 class="story-in story-retro relative m-0 -rotate-[4deg] text-[2.75rem] sm:text-[4rem] lg:text-[4.75rem]" style="--d:80ms">
                        <span class="block text-[0.62em]">{{ __('What makes') }}</span>
                        <span class="block">{{ __('MOFA') }}</span>
                        <span class="block">{{ __('different?') }}</span>
                        {!! $flower('story-spin absolute end-0 top-[24%] size-12 text-brown sm:size-16 lg:-end-4') !!}
                    </h3>

                    <div class="relative">
                        <ul class="m-0 flex -rotate-2 list-none flex-col gap-2.5 p-0 sm:gap-3">
                            @foreach ([__('Premium ingredients'), __('No unnecessary additives'), __('No hydrogenated oils'), __('Freshly made'), __('Just real food.')] as $i => $point)
                                <li class="story-in flex items-center gap-3.5" style="--d:{{ 260 + $i * 130 }}ms">
                                    <svg class="story-draw size-7 shrink-0 text-brown sm:size-8" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="--d:{{ 380 + $i * 130 }}ms" aria-hidden="true"><path pathLength="1" d="M5 17c3 3 5 6 7 9 4-9 9-16 15-21" /></svg>
                                    <span @class(['story-script text-[1.5rem] leading-tight sm:text-[2rem]', 'relative' => $loop->last])>
                                        {{ $point }}
                                        @if ($loop->last)
                                            <svg class="story-draw absolute -bottom-3 start-0 h-3 w-[110%]" viewBox="0 0 200 12" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" preserveAspectRatio="none" style="--d:1150ms" aria-hidden="true"><path pathLength="1" d="M2 10C60 4 130 2 198 3" /></svg>
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                        {{-- Little "spark" strokes, like the post --}}
                        <svg class="story-draw absolute -end-2 -top-6 hidden h-12 w-12 text-brown sm:block" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" style="--d:900ms" aria-hidden="true"><path pathLength="1" d="M8 32 20 16M14 36l20-8M10 22l2-18" /></svg>
                    </div>
                </div>
            </article>

            {{-- ===== 3. Simple & honest ===== --}}
            <article class="story-slide bg-[#f1e2dc]" :class="active === 2 && 'is-active'" :inert="active !== 2"
                     role="group" aria-roledescription="slide" aria-label="{{ __('Slide :n of :total', ['n' => 3, 'total' => $count]) }}">
                <svg class="story-blob absolute -end-20 -bottom-24 -z-10 h-[24rem] w-[24rem] text-mauve/20 lg:h-[32rem] lg:w-[32rem]" viewBox="0 0 200 200" aria-hidden="true">
                    <path fill="currentColor" d="M40 -66C54 -56 68 -44 74 -28C80 -12 78 8 70 26C62 44 48 60 30 68C12 76 -10 76 -30 68C-50 60 -68 44 -74 24C-80 4 -74 -20 -60 -36C-46 -52 -24 -60 -4 -66C16 -72 26 -76 40 -66Z" transform="translate(100 100)" />
                </svg>

                <div class="grid items-center gap-10 lg:grid-cols-[1.4fr_1fr] lg:gap-10">
                    <div class="relative -rotate-[5deg]">
                        @foreach ([__('Fresh ingredients.'), __('Real cold cuts.'), __('Good cheese.'), __('Simple & honest.')] as $i => $line)
                            <p class="story-in story-script m-0 text-[2.25rem] leading-[1.15] sm:text-[3.25rem] lg:text-[3.75rem]" style="--d:{{ 120 + $i * 170 }}ms; padding-inline-start: {{ $i * 0.45 }}em">{{ $line }}</p>
                        @endforeach
                        <svg class="story-draw mt-2 h-4 w-3/4 text-brown" viewBox="0 0 300 16" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" preserveAspectRatio="none" style="--d:900ms" aria-hidden="true"><path pathLength="1" d="M4 13C90 7 190 3 296 4" /></svg>
                    </div>

                    @if ($sandwich)
                        {{-- One of our sandwiches in the paper frame, tilted, with the stamp on its corner. --}}
                        <a href="{{ route('menu.item', ['item' => $sandwich]) }}" class="story-in group/photo relative mx-auto block w-full max-w-[26rem] no-underline" style="--d:420ms; --r:3deg">
                            <span class="block rounded-[1.625rem] bg-paper p-1.5 shadow-[0_24px_50px_-24px_rgb(75_44_22/0.6)] transition duration-500 group-hover/photo:-translate-y-1">
                                <span class="block aspect-[4/3] overflow-hidden rounded-[1.25rem] bg-cream">
                                    <img src="{{ $sandwich->coverUrl() }}" alt="{{ $sandwich->localized('name') }}" loading="lazy" decoding="async" class="size-full object-cover transition duration-700 group-hover/photo:scale-105">
                                </span>
                            </span>
                            <span class="absolute -end-5 -top-10 flex size-32 items-center justify-center rounded-full bg-paper text-brown shadow-md sm:-end-10 sm:-top-12 sm:size-36" aria-hidden="true">
                                <x-site.stamp-ring class="absolute inset-1 size-[calc(100%-0.5rem)]" />
                                <span class="flex size-14 items-center justify-center rounded-full bg-brown text-paper sm:size-16">
                                    <svg class="h-6 w-10" viewBox="0 0 96 52" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" aria-hidden="true"><path d="M10 46C3 46 1 40 4 33 10 17 28 7 48 7s38 10 44 26c3 7 1 13-6 13Z" /><path d="M30 30c4-8 9-13 15-16M44 32c4-8 9-13 15-16M58 33c4-7 8-11 13-14" /></svg>
                                </span>
                            </span>
                        </a>
                    @else
                        {{-- No photos yet: the stamp alone --}}
                        <div class="story-in relative mx-auto flex size-56 items-center justify-center text-brown sm:size-64" style="--d:500ms">
                            <x-site.stamp-ring class="absolute inset-0 size-full" />
                            <span class="flex size-28 items-center justify-center rounded-full bg-brown text-paper sm:size-32">
                                <svg class="h-10 w-16" viewBox="0 0 96 52" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M10 46C3 46 1 40 4 33 10 17 28 7 48 7s38 10 44 26c3 7 1 13-6 13Z" /><path d="M30 30c4-8 9-13 15-16M44 32c4-8 9-13 15-16M58 33c4-7 8-11 13-14" /></svg>
                            </span>
                        </div>
                    @endif
                </div>
            </article>

            {{-- ===== 4. The mission ===== --}}
            <article class="story-slide bg-[#eee5d2]" :class="active === 3 && 'is-active'" :inert="active !== 3"
                     role="group" aria-roledescription="slide" aria-label="{{ __('Slide :n of :total', ['n' => 4, 'total' => $count]) }}">
                <svg class="story-blob absolute -start-24 -top-28 -z-10 h-[26rem] w-[26rem] text-olive/20 lg:h-[34rem] lg:w-[34rem]" viewBox="0 0 200 200" aria-hidden="true">
                    <path fill="currentColor" d="M52 -62C66 -50 74 -30 76 -10C78 10 72 30 58 46C44 62 22 72 0 72C-22 72 -42 60 -56 44C-70 28 -78 6 -74 -14C-70 -34 -54 -52 -34 -64C-14 -76 38 -74 52 -62Z" transform="translate(100 100)" />
                </svg>

                <div class="grid items-center gap-10 lg:grid-cols-[1.1fr_1fr] lg:gap-14">
                    <div class="flex flex-col items-start gap-5">
                        <h3 class="story-in story-retro relative m-0 -rotate-3 text-[3.5rem] sm:text-[5rem] lg:text-[5.75rem]" style="--d:80ms">
                            @if (app()->getLocale() !== 'ar')
                                <span class="block text-[0.45em]">The</span>
                            @endif
                            <span class="block">{{ __('Mission') }}</span>
                            {!! $flower('story-spin absolute -end-12 top-1/3 size-12 text-brown sm:-end-16 sm:size-16') !!}
                        </h3>
                        <div class="-rotate-2">
                            <p class="story-in story-script m-0 max-w-[26rem] text-[1.75rem] leading-[1.25] sm:text-[2.25rem]" style="--d:300ms">{{ __('To make breakfast you can actually feel good about eating.') }}</p>
                            <svg class="story-draw mt-2 h-3 w-3/4 text-brown" viewBox="0 0 300 12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" preserveAspectRatio="none" style="--d:700ms" aria-hidden="true"><path pathLength="1" d="M3 10C90 5 190 2 297 3" /></svg>
                        </div>
                    </div>

                    <div class="flex flex-col items-center gap-8">
                        {{-- A taped-on note --}}
                        <div class="story-in relative w-full max-w-[25rem] rounded-[0.5rem] bg-[#fffaf4] px-7 pt-9 pb-7 shadow-[0_18px_36px_-20px_rgb(75_44_22/0.55)]" style="--d:520ms; --r:2deg">
                            <span class="absolute -top-3 start-8 h-6 w-20 -rotate-6 bg-mauve/45" aria-hidden="true"></span>
                            <span class="absolute -top-3 end-8 h-6 w-20 rotate-6 bg-olive/40" aria-hidden="true"></span>
                            <p class="story-script m-0 text-[1.5rem] leading-[1.3] sm:text-[1.75rem]">{{ __('We care about what goes inside, how it\'s made, and how it makes you feel after.') }}</p>
                            <svg class="story-draw story-beat absolute -bottom-5 end-6 size-10 text-terracotta" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" style="--d:1100ms" aria-hidden="true"><path pathLength="1" d="M16 27C8 21 4 16 4 11a6 6 0 0 1 12-1 6 6 0 0 1 12 1c0 5-4 10-12 16Z" /></svg>
                        </div>
                        <div class="story-in relative -rotate-6 self-end pe-12 lg:self-center" style="--d:900ms">
                            <p class="story-retro m-0 text-[1.75rem] sm:text-[2.25rem]">{{ __('Quality over shortcuts.') }}</p>
                            <svg class="story-draw h-3 w-full text-terracotta" viewBox="0 0 200 12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" preserveAspectRatio="none" style="--d:1250ms" aria-hidden="true"><path pathLength="1" d="M3 9C60 4 130 2 197 3" /></svg>
                            <svg class="story-draw absolute end-0 top-0 h-12 w-10 text-brown" viewBox="0 0 40 48" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" style="--d:1350ms" aria-hidden="true"><path pathLength="1" d="M8 14 30 4M10 24h26M8 34l22 10" /></svg>
                        </div>
                    </div>
                </div>
            </article>

            {{-- ===== 5. The vision (on brown) ===== --}}
            <article class="story-slide bg-brown text-paper" :class="active === 4 && 'is-active'" :inert="active !== 4"
                     role="group" aria-roledescription="slide" aria-label="{{ __('Slide :n of :total', ['n' => 5, 'total' => $count]) }}">
                <svg class="story-blob absolute -end-28 -top-28 -z-10 h-[26rem] w-[26rem] text-terracotta/35 lg:h-[34rem] lg:w-[34rem]" viewBox="0 0 200 200" aria-hidden="true">
                    <path fill="currentColor" d="M44 -60C58 -48 70 -30 74 -10C78 10 74 32 60 46C46 60 22 66 0 66C-22 66 -44 60 -58 44C-72 28 -78 4 -72 -18C-66 -40 -48 -58 -28 -68C-8 -78 30 -72 44 -60Z" transform="translate(100 100)" />
                </svg>

                <div class="grid items-center gap-10 lg:grid-cols-[1fr_1.15fr] lg:gap-14">
                    <div class="flex flex-col items-start gap-5">
                        <h3 class="story-in story-retro relative m-0 -rotate-3 text-paper text-[3.5rem] sm:text-[5rem] lg:text-[6rem]" style="--d:80ms">
                            @if (app()->getLocale() !== 'ar')
                                <span class="block text-[0.45em]">The</span>
                            @endif
                            <span class="block">{{ __('Vision') }}</span>
                            {!! $flower('story-spin absolute -end-12 -top-2 size-12 text-terracotta sm:-end-16 sm:size-16') !!}
                        </h3>
                        <ul class="m-0 flex list-none flex-wrap gap-2.5 p-0">
                            @foreach ([__('Loaded.'), __('Clean.'), __('Fresh.')] as $i => $word)
                                <li class="story-in rounded-pill border-[1.5px] border-paper/70 px-4 py-1.5 font-retro text-lg tracking-wide rtl:font-extrabold" style="--d:{{ 700 + $i * 140 }}ms; --r:{{ [-4, 2, -2][$i] }}deg">{{ $word }}</li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="flex -rotate-2 flex-col gap-4">
                        <p class="story-in story-script m-0 text-[1.75rem] leading-[1.25] sm:text-[2.375rem]" style="--d:280ms">{{ __('To make breakfast from outside feel just as good as making it yourself.') }}</p>
                        <svg class="story-draw h-3 w-2/3 text-terracotta" viewBox="0 0 300 12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" preserveAspectRatio="none" style="--d:620ms" aria-hidden="true"><path pathLength="1" d="M3 9C90 4 190 2 297 3" /></svg>
                        <p class="story-in story-script m-0 flex items-end gap-3 text-[1.5rem] leading-[1.25] text-paper/85 sm:text-[1.875rem]" style="--d:1100ms">
                            <span>{{ __('And something you actually look forward to eating.') }}</span>
                            <svg class="story-draw story-beat size-9 shrink-0 text-terracotta" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" style="--d:1300ms" aria-hidden="true"><path pathLength="1" d="M16 27C8 21 4 16 4 11a6 6 0 0 1 12-1 6 6 0 0 1 12 1c0 5-4 10-12 16Z" /></svg>
                        </p>
                    </div>
                </div>
            </article>
        </div>
        {{-- Previous / next on the panel's sides (they swap sides in Arabic) --}}
        <button type="button" @click="prev()" class="story-arrow start-3 xl:-start-6" aria-label="{{ __('Previous') }}">
            <x-mofa.icon name="arrow" :size="20" class="rotate-180 rtl:rotate-0" />
        </button>
        <button type="button" @click="next()" class="story-arrow end-3 xl:-end-6" aria-label="{{ __('Next') }}">
            <x-mofa.icon name="arrow" :size="20" class="rtl:rotate-180" />
        </button>
    </div>

    {{-- Story-style progress: one bar per slide, the current one fills up --}}
    {{-- On phones and tablets the arrows sit here, either side of the bars (on desktop they're on the panel). --}}
    <div class="mt-5 flex items-center gap-3 lg:block">
    <button type="button" @click="prev()" class="story-btn shrink-0 lg:hidden" aria-label="{{ __('Previous') }}">
        <x-mofa.icon name="arrow" :size="18" class="rotate-180 rtl:rotate-0" />
    </button>
    <div class="grid min-w-0 flex-1 gap-3 sm:gap-4" style="grid-template-columns: repeat({{ $count }}, minmax(0, 1fr))">
        @foreach ($slides as $i => $slide)
            <button type="button" @click="go({{ $i }})" class="group/tab flex flex-col gap-2 text-start"
                    :aria-current="active === {{ $i }} ? 'true' : 'false'" aria-label="{{ $slide['tab'] }}">
                <span class="block h-1 w-full overflow-hidden rounded-pill bg-brown/15">
                    <span class="block h-full rounded-pill bg-brown"
                          :style="`width: ${active === {{ $i }} ? progress * 100 : (active > {{ $i }} ? 100 : 0)}%`"></span>
                </span>
                <span class="hidden truncate text-sm transition-colors sm:block"
                      :class="active === {{ $i }} ? 'font-semibold text-brown' : 'text-ink-muted group-hover/tab:text-brown'">{{ $slide['tab'] }}</span>
            </button>
        @endforeach
    </div>
    <button type="button" @click="next()" class="story-btn shrink-0 lg:hidden" aria-label="{{ __('Next') }}">
        <x-mofa.icon name="arrow" :size="18" class="rtl:rotate-180" />
    </button>
    </div>
</section>
