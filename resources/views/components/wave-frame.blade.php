{{-- A cream panel with centered content. (The colour waves now live in the screen's corners: site/corners.) --}}
<div {{ $attributes->class('relative rounded-md bg-cream') }}>
    <div class="relative px-6 py-16 sm:px-12">
        {{ $slot }}
    </div>
</div>
