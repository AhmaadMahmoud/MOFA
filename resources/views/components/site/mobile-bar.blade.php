{{-- The main action, fixed in thumb reach on phones. Hidden from lg up, where the header carries it.
     With something in the cart it becomes "View your order". --}}
<div x-data class="fixed inset-x-0 bottom-0 z-30 border-t border-hairline bg-paper/95 px-4 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] backdrop-blur lg:hidden">
    <x-button :href="route('menu')" class="h-13 w-full" x-show="$store.cart.isEmpty">
        {{ __('Order for tomorrow') }}
        <x-mofa.icon name="arrow" :size="20" class="flip-rtl" />
    </x-button>
    <button type="button" x-cloak x-show="!$store.cart.isEmpty" @click="$store.cart.open = true"
            class="btn btn-primary h-13 w-full justify-between px-5">
        <span class="flex items-center gap-2">
            <span class="flex size-7 items-center justify-center rounded-pill bg-paper text-sm font-semibold text-brown" x-text="$store.cart.count"></span>
            {{ __('View your order') }}
        </span>
        <span class="font-semibold normal-case" x-text="$store.cart.money($store.cart.subtotal)"></span>
    </button>
</div>
