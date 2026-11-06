{{--
    The posts' frame: colour waves in the four corners of the screen, on every page.
    Fixed over the page so full-width bands don't hide them, but sized to the side margin
    (.mofa-corner), so they never cover text; never clickable. Physical corners in both languages,
    like the printed posts. Menus and the cart open over them.
--}}
{{-- Top corners: over the header bar (they sit beside the logo and the cart, never on them). --}}
<div class="pointer-events-none fixed inset-0 z-[45] overflow-hidden" aria-hidden="true">
    {{-- Top left: mauve under a terracotta wave. --}}
    <svg class="absolute top-0 left-0 mofa-corner -scale-y-100" viewBox="0 0 150 224" preserveAspectRatio="none">
        <path class="fill-terracotta" d="M0 0 C40 -20 60 20 70 70 C80 120 110 170 150 224 H0 Z" />
        <path class="fill-mauve" d="M0 80 C26 80 38 120 46 160 C54 192 70 212 90 224 H0 Z" />
    </svg>
    {{-- Top right: olive. --}}
    <svg class="absolute top-0 right-0 mofa-corner" viewBox="0 0 110 150" preserveAspectRatio="none">
        <path class="fill-olive" d="M110 0 V150 C70 140 50 110 38 72 C28 40 16 16 0 0 Z" />
    </svg>
</div>
{{-- Bottom corners: under the mobile bottom bar. --}}
<div class="pointer-events-none fixed inset-0 z-20 overflow-hidden" aria-hidden="true">
    {{-- Bottom left: terracotta and mauve. --}}
    <svg class="absolute bottom-0 left-0 mofa-corner" viewBox="0 0 150 224" preserveAspectRatio="none">
        <path class="fill-terracotta" d="M0 0 C40 -20 60 20 70 70 C80 120 110 170 150 224 H0 Z" />
        <path class="fill-mauve" d="M0 80 C26 80 38 120 46 160 C54 192 70 212 90 224 H0 Z" />
    </svg>
    {{-- Bottom right: olive. --}}
    <svg class="absolute right-0 bottom-0 mofa-corner -scale-y-100" viewBox="0 0 110 150" preserveAspectRatio="none">
        <path class="fill-olive" d="M110 0 V150 C70 140 50 110 38 72 C28 40 16 16 0 0 Z" />
    </svg>
</div>
