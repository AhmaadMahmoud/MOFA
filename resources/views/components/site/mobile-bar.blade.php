{{-- The main action, fixed in thumb reach on phones. Hidden from lg up, where the header carries it. --}}
<div class="fixed inset-x-0 bottom-0 z-30 border-t border-hairline bg-paper/95 px-4 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] backdrop-blur lg:hidden">
    <x-button :href="route('menu')" class="h-13 w-full">
        {{ __('Order for tomorrow') }}
        <x-mofa.icon name="arrow" :size="20" class="flip-rtl" />
    </x-button>
</div>
