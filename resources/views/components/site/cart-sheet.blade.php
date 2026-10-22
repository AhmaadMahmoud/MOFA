{{--
    The cart: a bottom sheet on phones, a side panel from the inline end on desktop.
    Everything here is Alpine ($store.cart); nothing is sent until checkout.
--}}
<div x-data @keydown.escape.window="$store.cart.open = false">
    {{-- "Added" toast --}}
    <div x-cloak x-show="$store.cart.toast && !$store.cart.open"
         x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="opacity-0"
         class="fixed inset-x-4 top-[calc(0.75rem+env(safe-area-inset-top))] z-50 mx-auto flex max-w-md items-center gap-3 rounded-md bg-brown py-2 ps-4 pe-2 text-paper shadow-soft"
         role="status" aria-live="polite">
        <x-mofa.icon name="check" :size="20" class="shrink-0" />
        <p class="m-0 min-w-0 flex-1 truncate text-sm">
            {{ __('Added') }}: <span class="font-semibold" x-text="$store.cart.toast"></span>
        </p>
        <button type="button" @click="$store.cart.open = true" class="min-h-10 shrink-0 rounded-pill px-3 text-sm font-semibold text-cream underline-offset-4 hover:underline">
            {{ __('View') }}
        </button>
    </div>

    <div x-cloak x-show="$store.cart.open" class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="cart-title">
        <div x-show="$store.cart.open" x-transition.opacity class="absolute inset-0 bg-brown/45" @click="$store.cart.open = false"></div>

        <section x-show="$store.cart.open" x-trap.noscroll.inert="$store.cart.open"
                 x-transition:enter="transition duration-250 ease-out"
                 x-transition:enter-start="translate-y-full lg:translate-y-0 lg:translate-x-full lg:rtl:-translate-x-full"
                 x-transition:enter-end="translate-y-0 lg:translate-x-0"
                 x-transition:leave="transition duration-200 ease-in"
                 x-transition:leave-start="translate-y-0 lg:translate-x-0"
                 x-transition:leave-end="translate-y-full lg:translate-y-0 lg:translate-x-full lg:rtl:-translate-x-full"
                 class="absolute inset-x-0 bottom-0 flex max-h-[88dvh] flex-col rounded-t-lg bg-paper lg:inset-y-0 lg:start-auto lg:end-0 lg:max-h-none lg:w-[26rem] lg:rounded-none lg:rounded-s-lg">
            {{-- Grab handle (phones) --}}
            <div class="flex justify-center pt-2.5 lg:hidden" aria-hidden="true"><span class="h-1.5 w-10 rounded-pill bg-brown/20"></span></div>

            <header class="flex items-center justify-between gap-3 ps-5 pe-3 pt-2 pb-3 lg:pt-6">
                <h2 id="cart-title" class="m-0 font-serif text-[1.75rem] font-bold">{{ __('Your order') }}</h2>
                <button type="button" class="flex size-11 items-center justify-center rounded-pill hover:bg-cream" @click="$store.cart.open = false" aria-label="{{ __('Close') }}">
                    <x-mofa.icon name="close" />
                </button>
            </header>

            {{-- Empty --}}
            <div x-show="$store.cart.isEmpty" class="flex flex-col items-center gap-4 px-6 pt-6 pb-[calc(2rem+env(safe-area-inset-bottom))] text-center">
                <span class="flex size-16 items-center justify-center rounded-full bg-cream text-brown"><x-mofa.icon name="bag" :size="28" /></span>
                <p class="m-0 text-lg">{{ __('Your order is empty.') }}</p>
                <x-button :href="route('menu')" class="w-full max-w-xs" x-on:click="$store.cart.open = false">{{ __('See the menu') }}</x-button>
            </div>

            {{-- Lines --}}
            <ul x-show="!$store.cart.isEmpty" class="m-0 flex-1 list-none divide-y divide-brown/12 overflow-y-auto overscroll-contain border-t border-brown/12 px-5 py-0">
                <template x-for="line in $store.cart.items" :key="line.id">
                    <li class="flex gap-3 py-4">
                        <div class="size-16 shrink-0 overflow-hidden rounded-[0.625rem] bg-cream">
                            <template x-if="line.image"><img :src="line.image" alt="" class="size-full object-cover"></template>
                        </div>
                        <div class="flex min-w-0 flex-1 flex-col gap-2">
                            <div class="flex items-start justify-between gap-3">
                                <p class="m-0 font-serif text-lg leading-tight font-bold" x-text="$store.cart.name(line)"></p>
                                <p class="m-0 shrink-0 font-semibold" x-text="$store.cart.money(line.price * line.qty)"></p>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center rounded-pill border border-brown/30">
                                    <button type="button" class="flex size-10 items-center justify-center rounded-pill hover:bg-cream" @click="$store.cart.dec(line.id)"
                                            :aria-label="@js(__('One less')) + ': ' + $store.cart.name(line)">
                                        <x-mofa.icon name="minus" :size="18" />
                                    </button>
                                    <span class="w-7 text-center font-semibold tabular-nums" x-text="line.qty" aria-live="polite"></span>
                                    <button type="button" class="flex size-10 items-center justify-center rounded-pill hover:bg-cream disabled:opacity-35" @click="$store.cart.inc(line.id)" :disabled="line.qty >= 20"
                                            :aria-label="@js(__('One more')) + ': ' + $store.cart.name(line)">
                                        <x-mofa.icon name="plus" :size="18" />
                                    </button>
                                </div>
                                <button type="button" class="flex min-h-10 items-center gap-1.5 px-2 text-sm text-ink-muted hover:text-brown" @click="$store.cart.set(line.id, 0)">
                                    <x-mofa.icon name="trash" :size="18" /> {{ __('Remove') }}
                                </button>
                            </div>
                        </div>
                    </li>
                </template>
            </ul>

            <footer x-show="!$store.cart.isEmpty" class="flex flex-col gap-3 border-t border-brown/15 px-5 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))]">
                <div class="flex items-baseline justify-between">
                    <span class="text-ink-muted">{{ __('Subtotal') }}</span>
                    <span class="font-serif text-2xl font-bold" x-text="$store.cart.money($store.cart.subtotal)"></span>
                </div>
                <p class="m-0 text-sm text-ink-muted">{{ __('Delivery fee and day are chosen at checkout.') }}</p>
                <x-button :href="route('checkout')" class="h-13 w-full">
                    {{ __('Checkout') }}
                    <x-mofa.icon name="arrow" :size="20" class="flip-rtl" />
                </x-button>
            </footer>
        </section>
    </div>
</div>
