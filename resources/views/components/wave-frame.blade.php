{{-- A cream panel with the brand's organic corner waves. Content stays centered and clear of the corners. --}}
<div {{ $attributes->class('relative isolate overflow-hidden rounded-md bg-cream') }}>
    <svg class="absolute inset-0 -z-10 size-full" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
        <path class="fill-mauve" d="M0 0 H22 C16 6 10 4 6 12 C3 18 4 24 0 28 Z" />
        <path class="fill-terracotta" d="M0 30 C6 24 6 16 12 12 C18 8 22 6 26 0 H34 C28 8 22 10 16 16 C10 22 8 32 0 38 Z" />
        <path class="fill-olive" d="M100 0 V24 C94 22 90 16 86 10 C83 5 80 2 76 0 Z" />
        <path class="fill-terracotta" d="M0 70 C8 74 12 82 18 88 C22 92 26 96 30 100 H0 Z" />
        <path class="fill-mauve" d="M0 86 C6 90 10 96 14 100 H0 Z" />
        <path class="fill-olive" d="M100 100 V80 C94 82 90 88 86 94 C84 97 82 99 80 100 Z" />
    </svg>
    <div class="relative px-6 py-16 sm:px-12">
        {{ $slot }}
    </div>
</div>
