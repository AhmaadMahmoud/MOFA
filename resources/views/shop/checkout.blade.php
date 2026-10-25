@php
    use App\Support\Money;
    use App\Support\Settings;

    $locale = app()->getLocale();
    $cart = $this->cart;
    $steps = [1 => __('Your details'), 2 => __('Delivery'), 3 => __('Review')];
    $tomorrow = now()->addDay()->toDateString();
    $cta = $step < 3 ? __('Continue') : __('Place order');
    $selectedSlot = collect($this->selectedDay?->slots ?? [])->first(fn ($o) => $o->slot->id === $slotId)?->slot;
@endphp
<div x-data
     x-init="$wire.loadCart($store.cart.quantities())"
     x-on:cart-changed.window="$wire.loadCart($event.detail)"
     x-on:cart-synced.window="$store.cart.sync($event.detail.lines)"
     x-on:order-placed.window="$store.cart.clear(false)"
     x-on:checkout-step.window="$nextTick(() => document.getElementById('checkout-top')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
     class="mx-auto max-w-[70rem] px-4 pt-2 pb-10 lg:px-10 lg:pt-6 lg:pb-20">

    <div id="checkout-top" class="scroll-mt-4"></div>

    @if (! $loaded)
        {{-- Waiting for the browser's cart --}}
        <div class="grid gap-6 py-6 lg:grid-cols-[minmax(0,1fr)_22rem]" aria-busy="true">
            <div class="h-96 animate-pulse rounded-lg bg-paper"></div>
            <div class="hidden h-80 animate-pulse rounded-lg bg-brown/15 lg:block"></div>
        </div>
    @elseif ($cart->isEmpty())
        <div class="mx-auto flex max-w-md flex-col items-center gap-5 py-16 text-center">
            <span class="flex size-20 items-center justify-center rounded-full bg-paper"><x-mofa.icon name="bag" :size="34" /></span>
            <h1 class="m-0 font-serif text-[2rem] font-bold">{{ __('Your order is empty.') }}</h1>
            <p class="m-0 text-ink-muted">{{ __('Pick something from the menu, then come back here.') }}</p>
            <x-button :href="route('menu')" class="w-full max-w-xs">{{ __('See the menu') }}</x-button>
        </div>
    @elseif (! $this->isOpen)
        <div class="mx-auto flex max-w-md flex-col items-center gap-5 py-16 text-center">
            <span class="flex size-20 items-center justify-center rounded-full bg-paper"><x-mofa.icon name="calendar" :size="34" /></span>
            <h1 class="m-0 font-serif text-[2rem] font-bold">{{ __('Online ordering opens soon') }}</h1>
            <p class="m-0 text-ink-muted">{{ __('Meanwhile, order through our Instagram messages.') }}</p>
            <x-button href="https://www.instagram.com/mofa20261/" target="_blank" rel="noopener" class="w-full max-w-xs">
                <x-mofa.icon name="instagram" :size="20" /> <span dir="ltr">@mofa20261</span>
            </x-button>
        </div>
    @else
        <h1 class="m-0 mb-5 font-serif text-[2.25rem] leading-tight font-bold lg:mb-8 lg:text-5xl">{{ __('Checkout') }}</h1>

        <div class="grid grid-cols-[minmax(0,1fr)] items-start gap-5 lg:grid-cols-[minmax(0,1fr)_22rem] lg:gap-10">

            {{-- ================= The form ================= --}}
            <form id="checkout-form" wire:submit="{{ $step < 3 ? 'next' : 'place' }}" novalidate class="flex flex-col">
                <div class="rounded-lg bg-paper">
                    {{-- Steps: numbered, because they are a sequence. Done steps can be revisited. --}}
                    <ol class="m-0 flex list-none items-center gap-2 border-b border-brown/12 px-5 py-4 sm:px-7" aria-label="{{ __('Steps') }}">
                        @foreach ($steps as $n => $label)
                            <li @class(['flex min-w-0 items-center gap-2', 'flex-1' => $n < 3]) @if ($n === $step) aria-current="step" @endif>
                                @if ($n < $step)
                                    <button type="button" wire:click="goTo({{ $n }})" class="group flex min-w-0 items-center gap-2" aria-label="{{ __('Back to :step', ['step' => $label]) }}">
                                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-brown text-paper"><x-mofa.icon name="check" :size="15" /></span>
                                        <span class="hidden truncate text-sm font-medium text-ink-muted group-hover:text-brown sm:inline">{{ $label }}</span>
                                    </button>
                                @else
                                    <span @class([
                                        'flex size-7 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                                        'bg-terracotta text-on-terracotta' => $n === $step,
                                        'border-[1.5px] border-brown/25 text-ink-muted' => $n > $step,
                                    ])>{{ $n }}</span>
                                    <span @class(['hidden truncate text-sm sm:inline', 'font-semibold text-brown' => $n === $step, 'text-ink-muted' => $n > $step])>{{ $label }}</span>
                                @endif
                                @if ($n < 3)
                                    <span @class(['mx-1 h-0.5 min-w-4 flex-1 rounded-pill', 'bg-brown' => $n < $step, 'bg-brown/15' => $n >= $step]) aria-hidden="true"></span>
                                @endif
                            </li>
                        @endforeach
                    </ol>

                    <div class="flex flex-col gap-6 px-5 py-6 sm:px-7 sm:py-7">
                        {{-- ---------- Step 1: details ---------- --}}
                        @if ($step === 1)
                            <div>
                                <h2 class="m-0 font-serif text-[1.75rem] font-bold">{{ __('Your details') }}</h2>
                                <p class="m-0 mt-1 text-ink-muted">{{ __('So we know who the order is for, and how to reach you.') }}</p>
                            </div>

                            <div class="field">
                                <label for="name" class="field-label">{{ __('Name') }}</label>
                                <input id="name" type="text" wire:model.blur="name" autocomplete="name" class="field-input" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                                @error('name') <p id="name-error" class="field-error"><x-mofa.icon name="alert" :size="16" /> {{ $message }}</p> @enderror
                            </div>

                            <div class="grid gap-6 sm:grid-cols-2 sm:gap-4">
                                <div class="field">
                                    <label for="phone" class="field-label">{{ __('Mobile number') }}</label>
                                    <input id="phone" type="tel" inputmode="tel" dir="ltr" wire:model.blur="phone" autocomplete="tel" placeholder="01012345678" class="field-input text-start rtl:text-end" @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror>
                                    @error('phone')
                                        <p id="phone-error" class="field-error"><x-mofa.icon name="alert" :size="16" /> {{ $message }}</p>
                                    @else
                                        <p class="field-hint m-0">{{ __('We call this number if we can\'t find the address.') }}</p>
                                    @enderror
                                </div>

                                <div class="field">
                                    <label for="email" class="field-label">{{ __('Email') }}</label>
                                    <input id="email" type="email" inputmode="email" dir="ltr" wire:model.blur="email" autocomplete="email" placeholder="name@example.com" class="field-input text-start rtl:text-end" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                                    @error('email')
                                        <p id="email-error" class="field-error"><x-mofa.icon name="alert" :size="16" /> {{ $message }}</p>
                                    @else
                                        <p class="field-hint m-0">{{ __('Your order updates arrive here.') }}</p>
                                    @enderror
                                </div>
                            </div>
                        @endif

                        {{-- ---------- Step 2: delivery ---------- --}}
                        @if ($step === 2)
                            <div>
                                <h2 class="m-0 font-serif text-[1.75rem] font-bold">{{ __('Delivery') }}</h2>
                                <p class="m-0 mt-1 text-ink-muted">{{ __('Pick the morning, the time and where to bring it.') }}</p>
                            </div>

                            {{-- Day --}}
                            {{-- One row that scrolls, however many days are open; arrows beside the label from sm up. --}}
                            <fieldset x-data="{
                                    atStart: true, atEnd: false,
                                    sync() { const t = this.$refs.days, x = Math.abs(t.scrollLeft); this.atStart = x < 4; this.atEnd = x + t.clientWidth >= t.scrollWidth - 4 },
                                    page(dir) { const t = this.$refs.days; t.scrollBy({ left: dir * t.clientWidth * 0.8 * (document.dir === 'rtl' ? -1 : 1), behavior: 'smooth' }) },
                                 }" x-init="$nextTick(() => { sync(); $refs.days.querySelector('[data-picked]')?.closest('label')?.scrollIntoView({ block: 'nearest', inline: 'nearest' }) })"
                                      class="m-0 flex min-w-0 flex-col gap-2.5 border-0 p-0">
                                <legend class="sr-only">{{ __('Day') }}</legend>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="field-label" aria-hidden="true">{{ __('Day') }}</span>
                                    <div class="hidden items-center gap-1.5 sm:flex">
                                        <button type="button" @click="page(-1)" :disabled="atStart" aria-label="{{ __('Earlier days') }}"
                                                class="flex size-9 items-center justify-center rounded-full border border-brown/15 bg-[#fffaf4] text-brown transition hover:border-brown/50 disabled:cursor-default disabled:opacity-35 disabled:hover:border-brown/15">
                                            <x-mofa.icon name="arrow" :size="16" class="rotate-180 rtl:rotate-0" />
                                        </button>
                                        <button type="button" @click="page(1)" :disabled="atEnd" aria-label="{{ __('Later days') }}"
                                                class="flex size-9 items-center justify-center rounded-full border border-brown/15 bg-[#fffaf4] text-brown transition hover:border-brown/50 disabled:cursor-default disabled:opacity-35 disabled:hover:border-brown/15">
                                            <x-mofa.icon name="arrow" :size="16" class="rtl:rotate-180" />
                                        </button>
                                    </div>
                                </div>
                                @if ($this->days->first()?->key() !== $tomorrow)
                                    <p class="field-hint m-0 -mt-1">{{ __('Orders placed after :time are delivered from the day after tomorrow.', ['time' => \Illuminate\Support\Carbon::createFromFormat('H:i', Settings::cutoff())->locale($locale)->translatedFormat('g:i A')]) }}</p>
                                @endif
                                <div class="relative">
                                <div x-ref="days" @scroll.debounce.50ms="sync()"
                                     class="-mx-5 flex snap-x gap-2 overflow-x-auto scroll-px-5 px-5 pb-1 [scrollbar-width:none] sm:mx-0 sm:scroll-px-0 sm:px-0 [&::-webkit-scrollbar]:hidden">
                                    @foreach ($this->days as $day)
                                        @php($open = $day->isAvailable())
                                        <label @class([
                                            'relative flex w-[4.75rem] shrink-0 snap-start cursor-pointer flex-col items-center gap-0.5 rounded-md border-[1.5px] px-1 py-2.5 text-center transition',
                                            'border-brown bg-brown text-paper' => $date === $day->key(),
                                            'border-brown/15 bg-[#fffaf4] hover:border-brown/50' => $date !== $day->key() && $open,
                                            'cursor-not-allowed border-transparent bg-brown/5 text-brown/35' => ! $open,
                                        ])>
                                            <input type="radio" name="date" value="{{ $day->key() }}" wire:model.live="date" class="sr-only" @disabled(! $open) @if ($date === $day->key()) data-picked @endif>
                                            <span class="text-[0.6875rem] font-medium">{{ $day->key() === $tomorrow ? __('Tomorrow') : $day->date->locale($locale)->translatedFormat('D') }}</span>
                                            <span class="font-serif text-[1.625rem] leading-none font-bold">{{ $day->date->format('j') }}</span>
                                            <span class="text-[0.6875rem]">{{ $open ? $day->date->locale($locale)->translatedFormat('M') : __('Full') }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                {{-- Soft edges and arrows: only where there is more to see. --}}
                                <div x-show="!atStart" x-transition.opacity class="pointer-events-none absolute inset-y-0 start-0 hidden w-10 bg-gradient-to-r from-paper to-transparent sm:block rtl:bg-gradient-to-l"></div>
                                <div x-show="!atEnd" x-transition.opacity class="pointer-events-none absolute inset-y-0 end-0 hidden w-10 bg-gradient-to-l from-paper to-transparent sm:block rtl:bg-gradient-to-r"></div>
                                </div>
                                @error('date') <p class="field-error"><x-mofa.icon name="alert" :size="16" /> {{ $message }}</p> @enderror
                            </fieldset>

                            {{-- Time --}}
                            @if ($this->selectedDay)
                                <fieldset class="m-0 flex flex-col gap-2.5 border-0 p-0">
                                    <legend class="field-label mb-2.5 p-0">{{ __('Time') }}</legend>
                                    <div class="grid grid-cols-2 gap-2.5">
                                        @foreach ($this->selectedDay->slots as $option)
                                            <label class="choice" wire:key="slot-{{ $option->slot->id }}">
                                                <input type="radio" name="slot" value="{{ $option->slot->id }}" wire:model.live="slotId" class="sr-only" @disabled(! $option->available)>
                                                <span class="choice-dot" aria-hidden="true"></span>
                                                <span class="flex min-w-0 flex-col">
                                                    <span class="font-semibold">{{ $option->slot->localized('label') }}</span>
                                                    @if (! $option->available)
                                                        <span class="text-xs text-ink-muted">{{ __('Full') }}</span>
                                                    @elseif ($option->remaining !== null && $option->remaining <= 3)
                                                        <span class="text-xs text-ink-muted">{{ trans_choice('Only :count place left|Only :count places left', $option->remaining) }}</span>
                                                    @endif
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('slotId') <p class="field-error"><x-mofa.icon name="alert" :size="16" /> {{ $message }}</p> @enderror
                                </fieldset>
                            @endif

                            {{-- Area --}}
                            <fieldset class="m-0 flex flex-col gap-2.5 border-0 p-0">
                                <legend class="field-label mb-2.5 p-0">{{ __('Area') }}</legend>
                                <div class="grid gap-2.5 sm:grid-cols-2">
                                    @foreach ($this->zones as $zone)
                                        <label class="choice" wire:key="zone-{{ $zone->id }}">
                                            <input type="radio" name="zone" value="{{ $zone->id }}" wire:model.live="zoneId" class="sr-only">
                                            <span class="choice-dot" aria-hidden="true"></span>
                                            <span class="flex-1 font-semibold">{{ $zone->localized('name') }}</span>
                                            <span class="text-sm text-ink-muted">{{ $zone->feeLabel() }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('zoneId') <p class="field-error"><x-mofa.icon name="alert" :size="16" /> {{ $message }}</p> @enderror
                            </fieldset>

                            @if ($this->zone && ! $this->zone->is_pickup)
                                <div class="field">
                                    <label for="address" class="field-label">{{ __('Address') }}</label>
                                    <textarea id="address" wire:model.blur="address" rows="2" dir="auto" autocomplete="street-address" placeholder="{{ __('Street, building, floor, apartment, landmark') }}" class="field-input" @error('address') aria-invalid="true" @enderror></textarea>
                                    @error('address') <p class="field-error"><x-mofa.icon name="alert" :size="16" /> {{ $message }}</p> @enderror
                                </div>
                            @elseif ($this->zone?->is_pickup)
                                <p class="m-0 flex items-start gap-2 rounded-md bg-cream/50 p-4 text-sm"><x-mofa.icon name="pin" :size="20" class="shrink-0" /> {{ __('We will send you the pickup location with the confirmation.') }}</p>
                            @endif

                            <div class="field">
                                <label for="notes" class="field-label">{{ __('Notes') }} <span class="font-normal text-ink-muted">({{ __('optional') }})</span></label>
                                <textarea id="notes" wire:model.blur="notes" rows="2" dir="auto" placeholder="{{ __('Anything we should know?') }}" class="field-input"></textarea>
                                @error('notes') <p class="field-error"><x-mofa.icon name="alert" :size="16" /> {{ $message }}</p> @enderror
                            </div>
                        @endif

                        {{-- ---------- Step 3: review ---------- --}}
                        @if ($step === 3)
                            <div>
                                <h2 class="m-0 font-serif text-[1.75rem] font-bold">{{ __('Review your order') }}</h2>
                                <p class="m-0 mt-1 text-ink-muted">{{ __('Check everything, then place the order.') }}</p>
                            </div>

                            <dl class="m-0 divide-y divide-brown/12 rounded-md border border-brown/12 bg-[#fffaf4]">
                                <div class="flex items-start gap-4 p-4">
                                    <x-mofa.icon name="heart" :size="22" class="mt-0.5 shrink-0 text-ink-muted" />
                                    <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                                        <dt class="text-sm text-ink-muted">{{ __('Your details') }}</dt>
                                        <dd class="m-0 font-semibold">{{ $name }}</dd>
                                        <dd class="m-0 text-sm rtl:text-end" dir="ltr">{{ $phone }}</dd>
                                        <dd class="m-0 text-sm rtl:text-end" dir="ltr">{{ $email }}</dd>
                                    </div>
                                    <button type="button" wire:click="goTo(1)" class="min-h-10 shrink-0 px-1 text-sm font-semibold underline underline-offset-4">{{ __('Edit') }}</button>
                                </div>
                                <div class="flex items-start gap-4 p-4">
                                    <x-mofa.icon name="calendar" :size="22" class="mt-0.5 shrink-0 text-ink-muted" />
                                    <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                                        <dt class="text-sm text-ink-muted">{{ $this->zone?->is_pickup ? __('Pickup') : __('Delivery') }}</dt>
                                        <dd class="m-0 font-semibold">{{ $this->selectedDay?->date->locale($locale)->translatedFormat('l j F') }} · {{ $selectedSlot?->localized('label') }}</dd>
                                        <dd class="m-0 text-sm">{{ $this->zone?->localized('name') }}@if ($this->zone && ! $this->zone->is_pickup && $address) · <span dir="auto">{{ $address }}</span>@endif</dd>
                                        @if ($notes)
                                            <dd class="m-0 text-sm text-ink-muted" dir="auto">{{ $notes }}</dd>
                                        @endif
                                    </div>
                                    <button type="button" wire:click="goTo(2)" class="min-h-10 shrink-0 px-1 text-sm font-semibold underline underline-offset-4">{{ __('Edit') }}</button>
                                </div>
                                <div class="flex items-start gap-4 p-4">
                                    <x-mofa.icon name="phone-pay" :size="22" class="mt-0.5 shrink-0 text-ink-muted" />
                                    <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                                        <dt class="text-sm text-ink-muted">{{ __('Payment') }}</dt>
                                        <dd class="m-0 font-semibold">InstaPay</dd>
                                        <dd class="m-0 text-sm">{{ __('After you place the order, you get the InstaPay details. Pay, then send us the receipt on WhatsApp.') }}</dd>
                                    </div>
                                </div>
                            </dl>
                            @error('cart') <p class="field-error">{{ $message }}</p> @enderror
                        @endif
                    </div>

                    {{-- Desktop: back link at the foot of the card (the main button lives in the summary). --}}
                    @if ($step > 1)
                        <div class="hidden border-t border-brown/12 px-7 py-4 lg:block">
                            <button type="button" wire:click="back" class="inline-flex min-h-10 items-center gap-2 text-sm font-semibold text-ink-muted hover:text-brown">
                                <x-mofa.icon name="arrow" :size="18" class="rotate-180 rtl:rotate-0" /> {{ __('Back') }}
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Phones: back and the main button in a bar at the bottom. --}}
                <div class="fixed inset-x-0 bottom-0 z-30 border-t border-hairline bg-paper/95 px-4 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] backdrop-blur lg:hidden">
                    <div class="flex items-center gap-3">
                        @if ($step > 1)
                            <button type="button" wire:click="back" class="btn btn-ghost h-13 w-13 shrink-0 px-0" aria-label="{{ __('Back') }}">
                                <x-mofa.icon name="arrow" :size="20" class="rotate-180 rtl:rotate-0" />
                            </button>
                        @endif
                        <button type="submit" wire:loading.attr="disabled" class="btn btn-primary h-13 flex-1 justify-between px-5">
                            <span wire:loading.remove wire:target="next,place">{{ $cta }}</span>
                            <span wire:loading wire:target="next,place">{{ __('One moment…') }}</span>
                            <span class="font-semibold normal-case">{{ Money::format($this->total()) }}</span>
                        </button>
                    </div>
                </div>
                <div class="h-24 lg:hidden" aria-hidden="true"></div>
            </form>

            {{-- ================= The receipt (summary) ================= --}}
            <aside x-data="{ open: false }"
                   class="mofa-receipt order-first text-paper lg:sticky lg:top-6 lg:order-none" aria-labelledby="summary-title">
                <button type="button" @click="open = !open" class="flex w-full items-center justify-between gap-3 px-5 pt-5 pb-4 text-start lg:pointer-events-none" :aria-expanded="open.toString()">
                    <span class="flex flex-col">
                        <span id="summary-title" class="font-serif text-[1.5rem] leading-tight font-bold">{{ __('Your order') }}</span>
                        <span class="text-sm text-paper/70">{{ trans_choice(':count item|:count items', $cart->count()) }}</span>
                    </span>
                    <span class="flex items-center gap-2 lg:hidden">
                        <span class="font-serif text-xl font-bold">{{ Money::format($this->total()) }}</span>
                        <svg class="size-4 transition" :class="open && 'rotate-180'" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6l5 5 5-5" /></svg>
                    </span>
                </button>

                {{-- Phones: folded until tapped. Desktop: always open (the !important beats x-show). --}}
                <div x-show="open" x-transition.opacity class="lg:block!">
                    <ul class="m-0 flex list-none flex-col gap-3 px-5 py-1">
                        @foreach ($cart->lines as $line)
                            <li class="flex items-center gap-3" wire:key="line-{{ $line->item->id }}">
                                <span class="relative flex size-12 shrink-0 rounded-[0.625rem] bg-paper/15">
                                    @if ($cover = $line->item->coverUrl())<img src="{{ $cover }}" alt="" class="size-full rounded-[0.625rem] object-cover">@endif
                                    <span class="absolute -end-1.5 -top-1.5 flex h-5 min-w-5 items-center justify-center rounded-pill bg-cream px-1 text-[0.6875rem] font-bold text-brown">{{ $line->quantity }}</span>
                                </span>
                                <span class="min-w-0 flex-1 truncate font-medium">{{ $line->item->localized('name') }}</span>
                                <span class="shrink-0 text-paper/90">{{ Money::format($line->total()) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <button type="button" @click="$store.cart.open = true" class="mx-5 mt-3 min-h-9 text-sm font-medium text-paper/75 underline underline-offset-4 hover:text-paper">{{ __('Change items') }}</button>

                    <dl class="m-0 mx-5 mt-3 flex flex-col gap-2 border-t border-dashed border-paper/25 pt-4">
                        <div class="flex justify-between"><dt class="text-paper/70">{{ __('Subtotal') }}</dt><dd class="m-0">{{ Money::format($cart->subtotal()) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-paper/70">{{ __('Delivery fee') }}</dt><dd class="m-0">{{ $this->zone ? $this->zone->feeLabel() : __('Next step') }}</dd></div>
                    </dl>
                    <div class="mx-5 mt-4 flex items-baseline justify-between border-t border-dashed border-paper/25 pt-4">
                        <span class="font-semibold">{{ __('Total') }}</span>
                        <span class="font-serif text-[2rem] leading-none font-bold">{{ Money::format($this->total()) }}</span>
                    </div>
                </div>

                {{-- Desktop: the main button, under the total it commits to. --}}
                <div class="hidden px-5 pt-5 pb-8 lg:block">
                    <button type="submit" form="checkout-form" wire:loading.attr="disabled" class="btn h-13 w-full bg-cream text-brown hover:bg-paper">
                        <span wire:loading.remove wire:target="next,place">{{ $cta }}</span>
                        <span wire:loading wire:target="next,place">{{ __('One moment…') }}</span>
                        <x-mofa.icon name="arrow" :size="20" class="flip-rtl" wire:loading.remove wire:target="next,place" />
                    </button>
                    <p class="m-0 mt-3 flex items-center justify-center gap-1.5 text-center text-xs text-paper/65">
                        <x-mofa.icon name="phone-pay" :size="16" /> {{ __('You pay by InstaPay and send the receipt on WhatsApp.') }}
                    </p>
                </div>
                <div class="h-5 lg:hidden" aria-hidden="true"></div>
            </aside>
        </div>
    @endif
</div>
