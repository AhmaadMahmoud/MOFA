@props(['item', 'size' => 'compact']) {{-- compact (menu cards) | large (item page) | bar (item page bottom bar) --}}
@if ($item->isOrderable())
    @php($name = $item->localized('name'))
    <div x-data="{ product: @js($item->cartPayload()) }" {{ $attributes->class('relative z-10') }}>
        @if ($size === 'large' || $size === 'bar')
            <x-button class="h-13 w-full" x-show="!$store.cart.qty(product.id)" x-on:click="$store.cart.add(product)">
                <x-mofa.icon name="plus" :size="20" /> {{ __('Add to order') }}
            </x-button>
            <div x-cloak x-show="$store.cart.qty(product.id)" class="flex h-13 items-center gap-3">
                <div class="flex h-full items-center rounded-pill border-[1.5px] border-brown">
                    <button type="button" class="flex size-12 items-center justify-center rounded-pill hover:bg-paper" x-on:click="$store.cart.dec(product.id)" aria-label="{{ __('One less') }}: {{ $name }}">
                        <x-mofa.icon name="minus" :size="20" />
                    </button>
                    <span class="w-8 text-center text-lg font-semibold tabular-nums" x-text="$store.cart.qty(product.id)" aria-live="polite"></span>
                    <button type="button" class="flex size-12 items-center justify-center rounded-pill hover:bg-paper" x-on:click="$store.cart.inc(product.id)" aria-label="{{ __('One more') }}: {{ $name }}">
                        <x-mofa.icon name="plus" :size="20" />
                    </button>
                </div>
                @if ($size === 'bar')
                    {{-- Narrow: just the bag and the count. --}}
                    <button type="button" class="btn btn-ghost relative h-full flex-1 px-3" x-on:click="$store.cart.open = true" aria-label="{{ __('View your order') }}">
                        <x-mofa.icon name="bag" :size="22" />
                        <span class="flex h-6 min-w-6 items-center justify-center rounded-pill bg-brown px-1.5 text-sm font-semibold text-paper" x-text="$store.cart.count"></span>
                    </button>
                @else
                    <x-button variant="ghost" class="h-full flex-1" x-on:click="$store.cart.open = true">{{ __('View your order') }}</x-button>
                @endif
            </div>
        @else
            <button type="button" x-show="!$store.cart.qty(product.id)" x-on:click.prevent="$store.cart.add(product)"
                    class="flex size-11 items-center justify-center rounded-pill bg-brown text-paper transition hover:-translate-y-px"
                    aria-label="{{ __('Add to order') }}: {{ $name }}">
                <x-mofa.icon name="plus" :size="20" />
            </button>
            <div x-cloak x-show="$store.cart.qty(product.id)" class="flex h-11 items-center rounded-pill bg-brown text-paper">
                <button type="button" class="flex size-11 items-center justify-center rounded-pill" x-on:click.prevent="$store.cart.dec(product.id)" aria-label="{{ __('One less') }}: {{ $name }}">
                    <x-mofa.icon name="minus" :size="18" />
                </button>
                <span class="w-5 text-center font-semibold tabular-nums" x-text="$store.cart.qty(product.id)" aria-live="polite"></span>
                <button type="button" class="flex size-11 items-center justify-center rounded-pill" x-on:click.prevent="$store.cart.inc(product.id)" aria-label="{{ __('One more') }}: {{ $name }}">
                    <x-mofa.icon name="plus" :size="18" />
                </button>
            </div>
        @endif
    </div>
@endif
